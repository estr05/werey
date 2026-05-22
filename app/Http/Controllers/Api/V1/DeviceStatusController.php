<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            'battery_level'   => ['required', 'integer', 'between:0,100'],
            'is_charging'     => ['required', 'boolean'],
            'connection_type' => ['required', 'string', 'in:wifi,cellular,none'],
            'signal_strength' => ['nullable', 'integer', 'between:0,4'],
            'has_internet'    => ['required', 'boolean'],
            'tracking_state'  => ['required', 'string'],
            'activity_status' => ['required', 'string'],
            'captured_at'     => ['required', 'string'],
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

        // Actualizar estado del dispositivo
        $device->update([
            'battery_level'   => $validated['battery_level'],
            'is_charging'     => $validated['is_charging'],
            'connection_type' => $validated['connection_type'],
            'signal_strength' => $validated['signal_strength'] ?? $device->signal_strength,
            'has_internet'    => $validated['has_internet'],
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
}
