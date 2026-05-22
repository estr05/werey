<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\LocationHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LocationController
 *
 * Recibe los frames de ubicación GPS enviados por la app móvil.
 * La app (warey_movil) envía a POST /api/v1/location con los campos
 * definidos en LocationFrame.toApiJson():
 *
 *   {
 *     "latitude"       : 14.064,
 *     "longitude"      : -87.206,
 *     "accuracy"       : 8.5,
 *     "speed"          : 1.2,
 *     "smoothed_speed" : 1.0,
 *     "altitude"       : 840.0,
 *     "movement_type"  : "WALKING",
 *     "tracking_state" : "SAFE_MOVING",
 *     "is_safe_zone"   : true,
 *     "zone_name"      : "Casa",
 *     "captured_at"    : "2026-05-22T14:00:00.000Z"
 *   }
 *
 * La autenticación se resuelve via Bearer Token de Sanctum (device_token:<id>).
 */
class LocationController extends Controller
{
    /**
     * POST /api/v1/location
     *
     * Recibe un frame GPS, actualiza la posición actual del dispositivo
     * y guarda un registro en location_histories para el mapa del dashboard.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'       => ['required', 'numeric', 'between:-90,90'],
            'longitude'      => ['required', 'numeric', 'between:-180,180'],
            'accuracy'       => ['nullable', 'numeric'],
            'speed'          => ['nullable', 'numeric'],
            'smoothed_speed' => ['nullable', 'numeric'],
            'altitude'       => ['nullable', 'numeric'],
            'movement_type'  => ['nullable', 'string', 'in:STATIC,WALKING,RUNNING,VEHICLE'],
            'tracking_state' => ['nullable', 'string'],
            'is_safe_zone'   => ['nullable', 'boolean'],
            'zone_name'      => ['nullable', 'string'],
            'captured_at'    => ['nullable', 'string'],
        ]);

        // Resolver el dispositivo desde el token de Sanctum (device_token:<id>)
        $token = $request->user()->currentAccessToken();

        if (! $token || ! str_contains($token->name, 'device_token:')) {
            return response()->json([
                'success' => false,
                'message' => 'Token de dispositivo no válido para transmisión de ubicación.',
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

        $movementType = $validated['movement_type'] ?? 'STATIC';

        // 1. Actualizar posición y estado actual del dispositivo
        //    También refrescamos last_seen para mantener el dispositivo como "online"
        $device->update([
            'latitude'   => $validated['latitude'],
            'longitude'  => $validated['longitude'],
            'activity'   => strtolower($movementType),
            'last_seen'  => now(),
        ]);

        // 2. Guardar en historial para el mapa del dashboard
        LocationHistory::create([
            'device_id'       => $device->id,
            'latitude'        => $validated['latitude'],
            'longitude'       => $validated['longitude'],
            'battery_level'   => $device->battery_level,       // Hereda del último device-status
            'is_charging'     => $device->is_charging,         // Hereda del último device-status
            'connection_type' => $device->connection_type,     // Hereda del último device-status
            'activity'        => strtolower($movementType),
            'movement_type'   => $movementType,
            'screen_active'   => $device->screen_active,       // Hereda del último device-status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Frame de ubicación recibido correctamente.',
            'data'    => [
                'device_id'     => $device->id,
                'movement_type' => $movementType,
                'last_seen'     => $device->last_seen,
            ],
        ], 200);
    }
}
