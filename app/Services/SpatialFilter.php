<?php

namespace App\Services;

use App\Models\Device;
use App\Models\LocationHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SpatialFilter
{
    /**
     * Procesa un punto GPS entrante.
     * Devuelve el array de datos modificado:
     * - Puede retornar `is_outlier => true` si es físicamente imposible.
     * - Puede sobreescribir `latitude` y `longitude` con versiones suavizadas
     *   (las originales se devuelven en `raw_latitude` y `raw_longitude`).
     */
    public function process(Device $device, array $point): array
    {
        $lat = (float) $point['latitude'];
        $lng = (float) $point['longitude'];
        // Si el móvil no envía accuracy, asumimos 25m (valor razonable para GPS típico)
        $accuracy = (float) ($point['accuracy'] ?? 25);
        $capturedAt = Carbon::parse($point['captured_at']);
        $activity = $point['activity'] ?? 'still';

        // Inicializamos los valores por defecto
        $point['raw_latitude'] = $lat;
        $point['raw_longitude'] = $lng;
        $point['is_outlier'] = false;
        
        // Calcular confidence score básico basado en accuracy
        // Accuracy de 5m -> 100, 150m -> 0 (escala relajada para GPS real)
        $confidence = max(0, min(100, 100 - (($accuracy - 5) * (100 / 145))));
        $point['confidence_score'] = (int) $confidence;

        // 1. Accuracy Threshold (Rechazo por mala triangulación extrema)
        // Umbral relajado a 150m: GPS en exteriores suele dar 5-50m,
        // en interiores o cañones urbanos puede llegar a 100-150m.
        // Solo rechazamos lecturas realmente inservibles (>150m).
        if ($accuracy > 150.0) {
            $point['is_outlier'] = true;
            $point['confidence_score'] = 0;
            Log::info("[SpatialFilter] Outlier detectado (Accuracy > 150): {$accuracy}m");
            return $point;
        }

        // Obtener últimos puntos válidos (no outliers)
        $lastPoints = LocationHistory::where('device_id', $device->id)
            ->where('is_outlier', false)
            ->orderBy('captured_at', 'desc')
            ->limit(3)
            ->get();

        if ($lastPoints->isEmpty()) {
            return $point; // Primer punto, nada con qué comparar
        }

        $lastPoint = $lastPoints->first();
        $timeDiffSeconds = $capturedAt->diffInSeconds($lastPoint->captured_at);

        // Si es un frame antiguo o duplicado en tiempo, lo omitimos para filtrado
        if ($timeDiffSeconds <= 0) {
            return $point;
        }

        // 2. Speed Validation (Física cinemática)
        $distance = $this->haversineDistance($lastPoint->latitude, $lastPoint->longitude, $lat, $lng);
        $speedKmh = ($distance / $timeDiffSeconds) * 3.6;

        if ($activity === 'walking' && $speedKmh > 15.0) {
            $point['is_outlier'] = true;
            $point['confidence_score'] = 0;
            Log::info("[SpatialFilter] Outlier cinemático (Walking a {$speedKmh} km/h)");
            return $point;
        }

        if ($speedKmh > 180.0) {
            $point['is_outlier'] = true;
            $point['confidence_score'] = 0;
            Log::info("[SpatialFilter] Outlier cinemático (Teletransporte a {$speedKmh} km/h)");
            return $point;
        }

        // 3. Boomerang Filter (Rebote de edificio)
        if ($lastPoints->count() >= 2) {
            $prev1 = $lastPoints[0]; // El último guardado
            $prev2 = $lastPoints[1]; // El penúltimo guardado
            
            // Verificamos si ocurrió rápido (< 15 segundos total)
            if ($capturedAt->diffInSeconds($prev2->captured_at) < 15) {
                $distPrev2ToCurrent = $this->haversineDistance($prev2->latitude, $prev2->longitude, $lat, $lng);
                $distPrev2ToPrev1 = $this->haversineDistance($prev2->latitude, $prev2->longitude, $prev1->latitude, $prev1->longitude);
                
                // Si el salto fue de más de 20m, pero regresamos a menos de 10m del punto original
                if ($distPrev2ToPrev1 > 20 && $distPrev2ToCurrent < 10) {
                    $angle = $this->calculateAngle($prev2->latitude, $prev2->longitude, $prev1->latitude, $prev1->longitude, $lat, $lng);
                    
                    if ($angle < 30) {
                        Log::info("[SpatialFilter] Boomerang detectado. Descartando punto actual.");
                        $point['is_outlier'] = true;
                        $point['confidence_score'] = 0;
                        // Nota: En una arquitectura más compleja, borraríamos el prev1 también.
                        return $point;
                    }
                }
            }
        }

        // 4. Suavizado (Moving Average)
        // Aplicamos Moving Average ponderado si la velocidad no es tan alta y hay jitter
        // Ideal para caminar y limpiar el zig-zag.
        if ($speedKmh < 40.0 && $lastPoints->count() >= 1) {
            $validPointsForSmoothing = [$point];
            
            // Peso base por accuracy
            $totalWeight = 1.0 / max($accuracy, 1);
            $sumLat = $lat * $totalWeight;
            $sumLng = $lng * $totalWeight;

            foreach ($lastPoints as $lp) {
                // Solo promediar si el punto es reciente (< 60 segundos)
                if ($capturedAt->diffInSeconds($lp->captured_at) < 60) {
                    $w = 1.0 / max($lp->accuracy ?? 10, 1);
                    $sumLat += $lp->latitude * $w;
                    $sumLng += $lp->longitude * $w;
                    $totalWeight += $w;
                }
            }

            $point['latitude'] = $sumLat / $totalWeight;
            $point['longitude'] = $sumLng / $totalWeight;
        }

        return $point;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    private function calculateAngle($lat1, $lon1, $lat2, $lon2, $lat3, $lon3)
    {
        $ax = $lon2 - $lon1;
        $ay = $lat2 - $lat1;
        $bx = $lon3 - $lon2;
        $by = $lat3 - $lat2;

        $dot = $ax * $bx + $ay * $by;
        $magA = sqrt($ax * $ax + $ay * $ay);
        $magB = sqrt($bx * $bx + $by * $by);

        if ($magA == 0 || $magB == 0) return 180;

        $cosTheta = $dot / ($magA * $magB);
        $cosTheta = max(-1, min(1, $cosTheta));
        return rad2deg(acos($cosTheta));
    }
}
