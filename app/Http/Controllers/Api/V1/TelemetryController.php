<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\LocationHistory;
use App\Services\SpatialFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelemetryController extends Controller
{
    /**
     * Recibe el paquete de telemetría completo desde la app móvil.
     *
     * Este endpoint es el principal punto de entrada para TODOS los datos
     * que envía la app móvil, tanto frames completos de GPS (con lat/lng)
     * como frames de estado del dispositivo (sin lat/lng, solo batería,
     * conectividad, señal, etc.).
     *
     * NOTA: La app móvil (warey_movil) envía desde 3 orígenes distintos:
     *   1. TelemetryEngine   → lat/lng + battery + connection → funciona OK
     *   2. DeviceStatusRepo  → battery + señal + tracking_state + activity_status
     *                         (SIN lat/lng) → este controlador ahora lo acepta
     *   3. LocationRepository → frames GPS detallados → POST /api/v1/location
     *
     * POST /api/v1/telemetry
     * Header: Authorization: Bearer <token>
     * Body (completo con GPS): {
     *   "latitude"        : 14.064,
     *   "longitude"       : -87.206,
     *   "battery_level"   : 85,
     *   "is_charging"     : false,
     *   "connection_type" : "wifi",
     *   "activity"        : "still",
     *   "screen_active"   : true,
     *   "movement_type"   : "STATIC"
     * }
     * Body (solo estado de dispositivo, SIN lat/lng): {
     *   "battery_level"   : 85,
     *   "is_charging"     : false,
     *   "connection_type" : "wifi",
     *   "signal_strength" : 4,
     *   "has_internet"    : true,
     *   "tracking_state"  : "UNSAFE_STATIC",
     *   "activity_status" : "IDLE",
     *   "screen_active"   : true,
     *   "captured_at"     : "2026-05-22T14:00:00.000Z"
     * }
     */
    public function update(Request $request): JsonResponse
    {
        // ── 1. Normalizar camelCase → snake_case ─────────────────────────────
        // Flutter normalmente envía JSON en camelCase (batteryLevel),
        // así que los unimos al formato snake_case que Laravel espera.
        $request->merge([
            'battery_level'      => $request->input('battery_level'),
            'is_charging'        => $request->input('is_charging'),
            'connection_type'    => $request->input('connection_type'),
            'movement_type'      => $request->input('movement_type'),
            'screen_active'      => $request->input('screen_active'),
            'signal_strength'    => $request->input('signal_strength'),
            'has_internet'       => $request->input('has_internet'),
            'tracking_state'     => $request->input('tracking_state'),
            'activity_status'    => $request->input('activity_status'),
            // NOTA: activity (still/moving de GPS) NO debe heredar de activity_status
            // (IDLE/CHARGING/WALKING del estado del dispositivo). Son conceptos separados.
            'activity'           => $request->input('activity'),
            // captured_at mantiene fallback a created_at por si hay
            // frames antiguos en la cola offline que usen ese nombre.
            'captured_at'        => $request->input('captured_at', $request->input('created_at')),
            'speed'              => $request->input('speed'),
            'smoothed_speed'     => $request->input('smoothed_speed'),
            'speed_kmh'          => $request->input('speed_kmh'),
            'intervalo_aplicado' => $request->input('intervalo_aplicado'),
            'motivo'             => $request->input('motivo'),
            // Nuevos campos para PostGIS, Heartbeat y Orientación
            'accuracy'           => $request->input('accuracy'),
            'bearing'            => $request->input('bearing'),
            'type'               => $request->input('type'),
        ]);

        // ── 2. Validación ────────────────────────────────────────────────────
        // latitude/longitude son OPCIONALES para permitir frames de estado
        // del dispositivo que NO incluyen coordenadas GPS.
        $validated = $request->validate([
            // GPS (opcional — presente solo en frames completos)
            'latitude'           => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'          => ['nullable', 'numeric', 'between:-180,180'],
            // Estado del dispositivo
            'battery_level'      => ['nullable', 'integer', 'between:0,100'],
            'is_charging'        => ['nullable', 'boolean'],
            'connection_type'    => ['nullable', 'string'],
            'signal_strength'    => ['nullable', 'integer', 'between:0,4'],
            'has_internet'       => ['nullable', 'boolean'],
            'tracking_state'     => ['nullable', 'string'],
            'activity_status'    => ['nullable', 'string'],
            'activity'           => ['nullable', 'string'],
            'movement_type'      => ['nullable', 'string'],
            'screen_active'      => ['nullable', 'boolean'],
            'captured_at'        => ['nullable', 'string'],
            'speed'              => ['nullable', 'numeric'],
            'smoothed_speed'     => ['nullable', 'numeric'],
            'speed_kmh'          => ['nullable', 'numeric'],
            'intervalo_aplicado' => ['nullable', 'integer'],
            'motivo'             => ['nullable', 'string'],
            'accuracy'           => ['nullable', 'numeric'],
            'bearing'            => ['nullable', 'numeric'],
            'type'               => ['nullable', 'string'],
        ]);

        // ── 3. Autenticación del dispositivo ─────────────────────────────────
        $token = $request->user()->currentAccessToken();
        
        if (!$token || !str_contains($token->name, 'device_token:')) {
            return response()->json([
                'success' => false,
                'message' => 'Token de dispositivo no válido para telemetría.',
            ], 403);
        }

        $deviceId = str_replace('device_token:', '', $token->name);
        $device = Device::where('user_id', $request->user()->id)->find($deviceId);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado o inactivo.',
            ], 404);
        }

        // ── 4. Log del payload recibido ──────────────────────────────────────
        $hasGps = $validated['latitude'] !== null && $validated['longitude'] !== null;
        
        // ── LÓGICA DE HEARTBEAT Y FILTROS GPS ────────────────────────────────
        $isHeartbeat = false;
        $accuracy = $validated['accuracy'] ?? null;

        // 1. Heartbeat explícito enviado por la app
        if (($validated['type'] ?? '') === 'heartbeat') {
            $isHeartbeat = true;
        }

        // 2. Filtro de Precisión (Anti-Basura)
        if ($hasGps && $accuracy !== null && $accuracy > 50.0) {
            $isHeartbeat = true; // Precisión muy mala. Se degrada a heartbeat para no arruinar el mapa.
        }

        // 3. Filtro Anti-Drift (Si está quieto y la posición casi no cambió, es heartbeat)
        if ($hasGps && !$isHeartbeat && ($validated['activity'] ?? 'still') === 'still') {
            if ($device->latitude == $validated['latitude'] && $device->longitude == $validated['longitude']) {
                $isHeartbeat = true;
            }
        }

        // 4. Filtrado Espacial Inteligente (SpatialFilter)
        $spatialData = [];
        if ($hasGps && !$isHeartbeat) {
            $filter = new SpatialFilter();
            $spatialData = $filter->process($device, $validated);

            if ($spatialData['is_outlier'] ?? false) {
                // Es basura, lo degradamos a heartbeat o simplemente lo ignoramos espacialmente
                $isHeartbeat = true; 
            } else {
                // Actualizamos las coordenadas con las suavizadas
                $validated['latitude'] = $spatialData['latitude'];
                $validated['longitude'] = $spatialData['longitude'];
            }
        }

        Log::info('[Telemetry] Paquete recibido', [
            'device_id'       => $device->id,
            'is_heartbeat'    => $isHeartbeat,
            'has_gps'         => $hasGps,
            'accuracy'        => $accuracy,
            'latitude'        => $validated['latitude'],
            'longitude'       => $validated['longitude'],
            'battery_level'   => $validated['battery_level'] ?? null,
            'connection_type' => $validated['connection_type'] ?? null,
        ]);

        // ── 5. Actualizar estado del dispositivo y cálculos secundarios ───────
        $speedKmh = $validated['speed_kmh'] ?? null;
        $intervalo = $validated['intervalo_aplicado'] ?? null;
        $motivo = $validated['motivo'] ?? null;
        
        // Calcular speed_kmh real si no viene
        if ($hasGps && $speedKmh === null) {
            $speedMs = $validated['smoothed_speed'] ?? $validated['speed'] ?? 0.0;
            $speedKmh = $speedMs * 3.6;
        }

        // LÓGICA DE BEARING (Orientación) Y FILTRO ANTI-JITTER
        $bearing = $validated['bearing'] ?? null;
        if ($bearing !== null) {
            // Normalización angular de seguridad (Wrap-around 0-359.99)
            $bearing = fmod((float)$bearing, 360.0);
            if ($bearing < 0) $bearing += 360.0;
            
            // Deadband Filter: Si la velocidad es < 2km/h, el GPS no sabe a donde apunta.
            // Ignoramos la rotación loca y congelamos la última dirección conocida.
            if (($speedKmh ?? 0) < 2.0) {
                $bearing = clone $device->bearing; // Ignoramos el nuevo, retenemos el actual
            }
        } else {
            $bearing = $device->bearing; // Si la app no manda bearing, mantenemos el anterior
        }

        if ($hasGps) {

            if ($intervalo === null) {
                $isSafe = str_contains($validated['tracking_state'] ?? $device->tracking_state ?? 'SAFE', 'SAFE');
                if ($isSafe) {
                    if ($speedKmh < 2.0) {
                        $intervalo = 30;
                    } else if ($speedKmh < 7.0) {
                        $intervalo = 5;
                    } else if ($speedKmh < 15.0) {
                        $intervalo = 4;
                    } else if ($speedKmh < 80.0) {
                        $intervalo = 3;
                    } else {
                        $intervalo = 2;
                    }
                } else {
                    if ($speedKmh < 2.0) {
                        $intervalo = 10;
                    } else if ($speedKmh < 7.0) {
                        $intervalo = 5;
                    } else if ($speedKmh < 15.0) {
                        $intervalo = 4;
                    } else if ($speedKmh < 80.0) {
                        $intervalo = 3;
                    } else {
                        $intervalo = 2;
                    }
                }
            }

            if ($motivo === null) {
                if ($speedKmh < 2.0) {
                    $isSafe = str_contains($validated['tracking_state'] ?? $device->tracking_state ?? 'SAFE', 'SAFE');
                    $motivo = $isSafe ? 'safe_static_slow' : 'unsafe_static_slow';
                } else if ($speedKmh < 7.0) {
                    $motivo = 'walking';
                } else if ($speedKmh < 15.0) {
                    $motivo = 'running';
                } else if ($speedKmh < 80.0) {
                    $motivo = 'vehicle';
                } else {
                    $motivo = 'high_speed';
                }
            }
        }

        $deviceUpdate = [
            'battery_level'   => $validated['battery_level'] ?? $device->battery_level,
            'is_charging'     => $validated['is_charging']   ?? $device->is_charging,
            'connection_type' => $validated['connection_type'] ?? $device->connection_type,
            // activity = clasificación de movimiento GPS (still/moving), NO activity_status
            'activity'        => $validated['activity']       ?? $device->activity,
            'screen_active'   => $validated['screen_active']  ?? $device->screen_active,
            'signal_strength' => $validated['signal_strength'] ?? $device->signal_strength,
            'has_internet'    => $validated['has_internet']   ?? $device->has_internet,
            'tracking_state'  => $validated['tracking_state'] ?? $device->tracking_state,
            'activity_status' => $validated['activity_status'] ?? $device->activity_status,
            'last_seen'       => now(),
            'last_status_at'  => $validated['captured_at']
                ? \Carbon\Carbon::parse($validated['captured_at'])
                : now(),
        ];

        // Solo actualizar lat/lng de verdad si NO es heartbeat y trae GPS válido
        if ($hasGps && !$isHeartbeat) {
            $deviceUpdate['latitude']  = $validated['latitude'];
            $deviceUpdate['longitude'] = $validated['longitude'];
            $deviceUpdate['last_accuracy'] = $accuracy;
            $deviceUpdate['last_location_at'] = now();
            
            // PostGIS GEOGRAPHY(Point) update
            $lon = (float) $validated['longitude'];
            $lat = (float) $validated['latitude'];
            $deviceUpdate['location'] = \Illuminate\Support\Facades\DB::raw("ST_SetSRID(ST_MakePoint({$lon}, {$lat}), 4326)");
            
            $deviceUpdate['bearing']   = $bearing;
            $deviceUpdate['speed_kmh'] = $speedKmh;
            $deviceUpdate['intervalo_aplicado'] = $intervalo;
            $deviceUpdate['motivo'] = $motivo;
        }

        $device->update($deviceUpdate);

        // ── 6. Crear LocationHistory SOLO si es GPS válido ─────────────
        if ($hasGps && !$isHeartbeat) {
            // Protección Anti-Duplicados (Offline sync de Flutter puede reintentar envíos)
            $capturedAtStr = $validated['captured_at'] ?? now()->toIso8601String();
            $capturedAt = \Carbon\Carbon::parse($capturedAtStr);
            
            $exists = LocationHistory::where('device_id', $device->id)
                ->where('captured_at', $capturedAt)
                ->exists();

            if (!$exists) {
                $lon = (float) $validated['longitude'];
                $lat = (float) $validated['latitude'];

                LocationHistory::create([
                    'device_id'          => $device->id,
                    'latitude'           => $validated['latitude'], // Posiblemente suavizada
                    'longitude'          => $validated['longitude'], // Posiblemente suavizada
                    'raw_latitude'       => $spatialData['raw_latitude'] ?? $validated['latitude'],
                    'raw_longitude'      => $spatialData['raw_longitude'] ?? $validated['longitude'],
                    'confidence_score'   => $spatialData['confidence_score'] ?? 100,
                    'is_outlier'         => $spatialData['is_outlier'] ?? false,
                    'location'           => \Illuminate\Support\Facades\DB::raw("ST_SetSRID(ST_MakePoint({$lon}, {$lat}), 4326)"),
                    'accuracy'           => $accuracy,
                    'bearing'            => $bearing,
                    'captured_at'        => $capturedAt,
                    'battery_level'      => $validated['battery_level']  ?? $device->battery_level,
                    'is_charging'        => $validated['is_charging']    ?? $device->is_charging,
                    'connection_type'    => $validated['connection_type'] ?? $device->connection_type,
                    // activity en LocationHistory = movimiento GPS (still/moving), NO activity_status
                    'activity'           => $validated['activity']       ?? 'unknown',
                    'movement_type'      => $validated['movement_type']  ?? 'STATIC',
                    'screen_active'      => $validated['screen_active']  ?? $device->screen_active,
                    'speed_kmh'          => $speedKmh,
                    'intervalo_aplicado' => $intervalo,
                    'motivo'             => $motivo,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Telemetría recibida correctamente.',
            'data'    => [
                'device_id' => $device->id,
                'last_seen' => $device->last_seen,
            ],
        ], 200);
    }

    /**
     * Devuelve el historial de telemetría de un dispositivo (últimas N entradas).
     * Útil para que la app móvil pueda mostrar su propio historial local.
     *
     * GET /api/v1/telemetry/{identifier}/history?limit=50
     * Header: Authorization: Bearer <token>
     */
    public function history(Request $request, string $identifier): JsonResponse
    {
        $device = Device::where('identifier', $identifier)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado o no autorizado.',
            ], 404);
        }

        $limit   = min((int) $request->query('limit', 50), 200); // máx 200 registros

        $history = $device->locationHistories()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['latitude', 'longitude', 'battery_level', 'is_charging',
                   'connection_type', 'activity', 'movement_type', 'screen_active', 'created_at']);

        return response()->json([
            'success' => true,
            'data'    => $history,
        ], 200);
    }
}
