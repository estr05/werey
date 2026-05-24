<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\LocationHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelemetryController extends Controller
{
    /**
     * Recibe el paquete de telemetría completo desde la app móvil.
     * Actualiza el estado actual del dispositivo y guarda un registro en el historial.
     *
     * POST /api/v1/telemetry
     * Header: Authorization: Bearer <token>
     * Body: {
     *   "identifier"     : "uuid-del-dispositivo",
     *   "latitude"       : 14.064,
     *   "longitude"      : -87.206,
     *   "battery_level"  : 85,
     *   "is_charging"    : false,
     *   "connection_type": "wifi",
     *   "activity"       : "still",
     *   "screen_active"  : true
     * }
     */
    public function update(Request $request): JsonResponse
    {
        // Flutter normalmente envía JSON en camelCase (batteryLevel),
        // así que los unimos al formato snake_case que Laravel espera.
        $request->merge([
            'battery_level'   => $request->input('batteryLevel', $request->input('battery_level')),
            'is_charging'     => $request->input('isCharging', $request->input('is_charging')),
            'connection_type' => $request->input('connectionType', $request->input('connection_type')),
            'movement_type'   => $request->input('movementType', $request->input('movement_type')),
            'screen_active'   => $request->input('screenActive', $request->input('screen_active')),
        ]);

        $validated = $request->validate([
            'latitude'        => ['required', 'numeric', 'between:-90,90'],
            'longitude'       => ['required', 'numeric', 'between:-180,180'],
            'battery_level'   => ['nullable', 'integer', 'between:0,100'],
            'is_charging'     => ['nullable', 'boolean'],
            'connection_type' => ['nullable', 'string'],
            'activity'        => ['nullable', 'string'],
            'movement_type'   => ['nullable', 'string'],
            'screen_active'   => ['nullable', 'boolean'],
        ]);

        $token = $request->user()->currentAccessToken();
        
        if (!$token || !str_contains($token->name, 'device_token:')) {
            return response()->json([
                'success' => false,
                'message' => 'Token de dispositivo no válido para telemetría.',
            ], 403);
        }

        // Extraer el ID interno del dispositivo del nombre del token de Sanctum
        $deviceId = str_replace('device_token:', '', $token->name);

        // Buscar el dispositivo activo correspondiente por su ID real
        $device = Device::where('user_id', $request->user()->id)->find($deviceId);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado o inactivo.',
            ], 404);
        }

        Log::info('[Telemetry] Paquete recibido', [
            'device_id'      => $device->id,
            'latitude'       => $validated['latitude'],
            'longitude'      => $validated['longitude'],
            'battery_level'  => $validated['battery_level'] ?? null,
            'is_charging'    => $validated['is_charging'] ?? null,
            'connection_type'=> $validated['connection_type'] ?? null,
            'activity'       => $validated['activity'] ?? null,
            'movement_type'  => $validated['movement_type'] ?? null,
            'screen_active'  => $validated['screen_active'] ?? null,
        ]);

        // 1. Actualizar el estado ACTUAL del dispositivo (última posición conocida)
        $device->update([
            'latitude'        => $validated['latitude'],
            'longitude'       => $validated['longitude'],
            'battery_level'   => $validated['battery_level'] ?? $device->battery_level,
            'is_charging'     => $validated['is_charging']   ?? $device->is_charging,
            'connection_type' => $validated['connection_type'] ?? $device->connection_type,
            'activity'        => $validated['activity']       ?? $device->activity,
            'screen_active'   => $validated['screen_active']  ?? $device->screen_active,
            'last_seen'       => now(),
        ]);

        // 2. Crear registro en el historial (para gráficas y rutas del dashboard)
        LocationHistory::create([
            'device_id'       => $device->id,
            'latitude'        => $validated['latitude'],
            'longitude'       => $validated['longitude'],
            'battery_level'   => $validated['battery_level']  ?? null,
            'is_charging'     => $validated['is_charging']    ?? false,
            'connection_type' => $validated['connection_type'] ?? null,
            'activity'        => $validated['activity']       ?? 'unknown',
            'movement_type'   => $validated['movement_type']  ?? 'STATIC',
            'screen_active'   => $validated['screen_active']  ?? false,
        ]);

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
