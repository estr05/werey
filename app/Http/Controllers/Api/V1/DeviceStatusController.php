<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DeviceStatusController
 *
 * Recibe los frames de estado del dispositivo enviados por la app móvil.
 * La app (warey_movil) envía a POST /api/v1/device-status con los campos
 * definidos en DeviceStatusFrame.toApiJson():
 *
 *   {
 *     "battery_level"    : 85,
 *     "is_charging"      : false,
 *     "connection_type"  : "wifi",
 *     "signal_strength"  : 4,
 *     "has_internet"     : true,
 *     "tracking_state"   : "UNSAFE_STATIC",
 *     "activity_status"  : "IDLE",
 *     "captured_at"      : "2026-05-22T14:00:00.000Z"
 *   }
 *
 * A diferencia del endpoint /telemetry, este endpoint NO requiere lat/lng.
 * Se enfoca exclusivamente en el estado del hardware y conectividad.
 * La autenticación se resuelve via Bearer Token de Sanctum (device_token:<id>).
 */
class DeviceStatusController extends Controller
{
    /**
     * POST /api/v1/device-status
     *
     * Recibe un frame de estado del dispositivo, actualiza los campos
     * correspondientes en la tabla devices y retorna confirmación.
     */
    public function store(Request $request): JsonResponse
    {
        // ── Normalizar camelCase → snake_case ──────────────────────────────
        // Por si el móvil envía camelCase (batteryLevel, isCharging, etc.)
        $request->merge([
            'battery_level'   => $request->input('battery_level', $request->input('batteryLevel')),
            'is_charging'     => $request->input('is_charging', $request->input('isCharging')),
            'connection_type' => $request->input('connection_type', $request->input('connectionType')),
            'signal_strength' => $request->input('signal_strength', $request->input('signalStrength')),
            'has_internet'    => $request->input('has_internet', $request->input('hasInternet')),
            'tracking_state'  => $request->input('tracking_state', $request->input('trackingState')),
            'activity_status' => $request->input('activity_status', $request->input('activityStatus')),
            'captured_at'     => $request->input('captured_at', $request->input('capturedAt')),
            'screen_active'   => $request->input('screen_active', $request->input('screenActive')),
        ]);

        $validated = $request->validate([
            'battery_level'   => ['required', 'integer', 'between:0,100'],
            'is_charging'     => ['required', 'boolean'],
            'connection_type' => ['required', 'string'],
            'signal_strength' => ['nullable', 'integer', 'between:0,4'],
            'has_internet'    => ['required', 'boolean'],
            'tracking_state'  => ['required', 'string'],
            'activity_status' => ['required', 'string'],
            'captured_at'     => ['required', 'string'],
            'screen_active'   => ['nullable', 'boolean'],
        ]);

        // Resolver el dispositivo desde el token de Sanctum (device_token:<id>)
        $token = $request->user()->currentAccessToken();

        if (! $token || ! str_contains($token->name, 'device_token:')) {
            return response()->json([
                'success' => false,
                'message' => 'Token de dispositivo no válido para estado del dispositivo.',
            ], 403);
        }

        $deviceId = str_replace('device_token:', '', $token->name);
        $device   = Device::where('user_id', $request->user()->id)->find($deviceId);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado o no autorizado.',
            ], 404);
        }

        // Log del payload recibido (útil para debug en Railway)
        Log::info('[DeviceStatus] Frame recibido', [
            'device_id'       => $device->id,
            'battery_level'   => $validated['battery_level'],
            'is_charging'     => $validated['is_charging'],
            'connection_type_raw' => $validated['connection_type'],
            'tracking_state'  => $validated['tracking_state'],
            'activity_status' => $validated['activity_status'],
            'screen_active'   => $validated['screen_active'] ?? null,
        ]);

        // Normalizar connection_type a minúsculas (trim + case-insensitive)
        $connectionType = strtolower(trim($validated['connection_type']));
        
        // Mapear valores comunes al estándar interno
        $connectionMap = [
            'mobile' => 'cellular',
            '4g'     => 'cellular',
            '5g'     => 'cellular',
            'lte'    => 'cellular',
            'edge'   => 'cellular',
            '3g'     => 'cellular',
        ];
        $normalizedType = $connectionMap[$connectionType] ?? $connectionType;

        // Log del valor normalizado
        Log::info('[DeviceStatus] Normalizado', [
            'device_id'       => $device->id,
            'connection_type_original' => $validated['connection_type'],
            'connection_type_normalized' => $normalizedType,
        ]);

        // Actualizar estado del dispositivo
        $device->update([
            'battery_level'   => $validated['battery_level'],
            'is_charging'     => $validated['is_charging'],
            'connection_type' => $normalizedType,
            'signal_strength' => $validated['signal_strength'] ?? $device->signal_strength,
            'has_internet'    => $validated['has_internet'],
            'screen_active'   => $validated['screen_active'] ?? $device->screen_active,
            'tracking_state'  => $validated['tracking_state'],
            'activity_status' => $validated['activity_status'],
            'last_status_at'  => \Carbon\Carbon::parse($validated['captured_at']),
            'last_seen'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado del dispositivo actualizado correctamente.',
            'data'    => [
                'device_id' => $device->id,
                'last_seen' => $device->last_seen,
            ],
        ], 200);
    }

    /**
     * POST /api/v1/device-status/batch
     *
     * Recibe un lote (array) de frames de estado.
     * Dado que actualmente no guardamos un histórico de estos estados,
     * simplemente tomamos el más reciente y actualizamos el dispositivo.
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if (! $token || ! str_contains($token->name, 'device_token:')) {
            return response()->json([
                'success' => false,
                'message' => 'Token de dispositivo no válido para estado del dispositivo.',
            ], 403);
        }

        $deviceId = str_replace('device_token:', '', $token->name);
        $device   = Device::where('user_id', $request->user()->id)->find($deviceId);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado o no autorizado.',
            ], 404);
        }

        $frames = $request->input('frames', []);
        if (!is_array($frames) || empty($frames)) {
            return response()->json([
                'success' => false,
                'message' => 'El lote de frames está vacío o no es un arreglo.',
            ], 400);
        }

        $latestFrame = null;
        $latestCapturedAt = null;

        foreach ($frames as $frame) {
            $capturedAtStr = $frame['capturedAt'] ?? $frame['captured_at'] ?? now()->toIso8601String();
            $capturedAtDate = \Carbon\Carbon::parse($capturedAtStr);

            if ($latestCapturedAt === null || $capturedAtDate->greaterThan($latestCapturedAt)) {
                $latestCapturedAt = $capturedAtDate;
                $latestFrame = $frame;
            }
        }

        if ($latestFrame !== null) {
            $connectionType = strtolower(trim($latestFrame['connection_type'] ?? 'unknown'));
            $connectionMap = [
                'mobile' => 'cellular',
                '4g'     => 'cellular',
                '5g'     => 'cellular',
                'lte'    => 'cellular',
                'edge'   => 'cellular',
                '3g'     => 'cellular',
            ];
            $normalizedType = $connectionMap[$connectionType] ?? $connectionType;

            $device->update([
                'battery_level'   => $latestFrame['battery_level'] ?? $device->battery_level,
                'is_charging'     => $latestFrame['is_charging'] ?? $device->is_charging,
                'connection_type' => $normalizedType,
                'signal_strength' => $latestFrame['signal_strength'] ?? $device->signal_strength,
                'has_internet'    => $latestFrame['has_internet'] ?? $device->has_internet,
                'screen_active'   => $latestFrame['screen_active'] ?? $device->screen_active,
                'tracking_state'  => $latestFrame['tracking_state'] ?? $device->tracking_state,
                'activity_status' => $latestFrame['activity_status'] ?? $device->activity_status,
                'last_status_at'  => $latestCapturedAt,
                'last_seen'       => now(),
            ]);
        } else {
            $device->update(['last_seen' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => count($frames) . ' frames de estado procesados correctamente.',
            'data'    => [
                'device_id' => $device->id,
                'last_seen' => $device->last_seen,
            ],
        ], 200);
    }
}
