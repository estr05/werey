<?php

namespace App\Services;

use App\Models\Device;

/**
 * TelemetryHelper
 *
 * Contiene la lógica compartida de cálculos de telemetría que usan
 * tanto TelemetryController como LocationController, eliminando
 * la duplicación de código.
 *
 * Métodos principales:
 *   - calculateSpeedKmh(): Calcula velocidad en km/h desde smoothed_speed o speed
 *   - calculateInterval(): Determina el intervalo de captura según velocidad y estado seguro
 *   - calculateMotivo(): Clasifica el motivo del movimiento basado en velocidad
 *   - processBearing(): Normaliza bearing y aplica deadband filter anti-jitter
 */
class TelemetryHelper
{
    /**
     * Calcula speed_kmh a partir de los valores del frame.
     * Prioriza speed_kmh si ya viene calculado, luego smoothed_speed, luego speed.
     */
    public function calculateSpeedKmh(?float $speedKmh, ?float $smoothedSpeedMs, ?float $speedMs): float
    {
        if ($speedKmh !== null) {
            return $speedKmh;
        }

        $speedMs = $smoothedSpeedMs ?? $speedMs ?? 0.0;
        return $speedMs * 3.6;
    }

    /**
     * Determina el intervalo_aplicado (segundos entre capturas) según:
     * - Velocidad actual
     * - Si está en zona segura o no (tracking_state / is_safe_zone)
     *
     * Tabla de intervalos:
     *   SAFE:   < 2km/h → 30s | < 7 → 5s | < 15 → 4s | < 80 → 3s | >= 80 → 2s
     *   UNSAFE: < 2km/h → 10s | < 7 → 5s | < 15 → 4s | < 80 → 3s | >= 80 → 2s
     */
    public function calculateInterval(float $speedKmh, bool $isSafe): int
    {
        if ($speedKmh < 2.0) {
            return $isSafe ? 30 : 10;
        } elseif ($speedKmh < 7.0) {
            return 5;
        } elseif ($speedKmh < 15.0) {
            return 4;
        } elseif ($speedKmh < 80.0) {
            return 3;
        } else {
            return 2;
        }
    }

    /**
     * Clasifica el motivo del movimiento basado en la velocidad y estado seguro.
     *
     * Motivos:
     *   < 2 km/h  → safe_static_slow | unsafe_static_slow (según isSafe)
     *   < 7 km/h  → walking
     *   < 15 km/h → running
     *   < 80 km/h → vehicle
     *   >= 80     → high_speed
     */
    public function calculateMotivo(float $speedKmh, bool $isSafe): string
    {
        if ($speedKmh < 2.0) {
            return $isSafe ? 'safe_static_slow' : 'unsafe_static_slow';
        } elseif ($speedKmh < 7.0) {
            return 'walking';
        } elseif ($speedKmh < 15.0) {
            return 'running';
        } elseif ($speedKmh < 80.0) {
            return 'vehicle';
        } else {
            return 'high_speed';
        }
    }

    /**
     * Determina si la velocidad actual se considera "estática" (< 2 km/h).
     */
    public function isStaticSpeed(float $speedKmh): bool
    {
        return $speedKmh < 2.0;
    }

    /**
     * Procesa y normaliza el bearing (orientación/dirección).
     *
     * 1. Normaliza el ángulo a 0-359.99 (wrap-around).
     * 2. Aplica deadband filter: si la velocidad es < 2 km/h,
     *    el GPS no puede determinar dirección confiable, así que
     *    retorna el bearing anterior (congela la última dirección conocida).
     *
     * @param float|null $newBearing  Bearing enviado por el dispositivo (0-360)
     * @param float|null $currentBearing Bearing actual en la BD (del device)
     * @param float      $speedKmh   Velocidad actual en km/h
     * @return float|null Bearing procesado (o null si no hay datos)
     */
    public function processBearing(?float $newBearing, ?float $currentBearing, float $speedKmh): ?float
    {
        if ($newBearing === null) {
            return $currentBearing; // No enviaron bearing, mantener el anterior
        }

        // Normalización angular (wrap-around 0-359.99)
        $bearing = fmod((float) $newBearing, 360.0);
        if ($bearing < 0) {
            $bearing += 360.0;
        }

        // Deadband Filter: si velocidad < 2 km/h, el GPS no sabe dirección
        if ($this->isStaticSpeed($speedKmh)) {
            return $currentBearing; // Congelar última dirección conocida
        }

        return $bearing;
    }
}
