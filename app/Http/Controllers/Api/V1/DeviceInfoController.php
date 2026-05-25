<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DeviceInfoController
 *
 * Endpoints para que la app móvil obtenga información de su propio dispositivo
 * usando exclusivamente el device_token (sin necesidad de conocer el identifier).
 *
 * GET /api/v1/device           → Dispositivo asociado al token actual
 * GET /api/v1/device/safe-places → Zonas seguras del dispositivo del token actual
 */
class DeviceInfoController extends Controller
{
    /**
     * Devuelve los datos del dispositivo asociado al device_token actual.
     *
     * GET /api/v1/device
     * Header: Authorization: Bearer <device_token>
     *
     * Resuelve el dispositivo desde el nombre del token Sanctum (device_token:<id>).
     * Útil para que la app móvil obtenga su propia información sin tener que
     * almacenar el identifier después del handshake.
     */
    public function show(Request $request): JsonResponse
    {
        $device = $this->resolveDeviceFromToken($request);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado o token inválido.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $device->id,
                'identifier'       => $device->identifier,
                'alias'            => $device->alias,
                'device_manufacturer' => $device->device_manufacturer,
                'device_model'     => $device->device_model,
                'os_version'       => $device->os_version,
                'app_version'      => $device->app_version,
                'latitude'         => $device->latitude,
                'longitude'        => $device->longitude,
                'battery_level'    => $device->battery_level,
                'is_charging'      => $device->is_charging,
                'connection_type'  => $device->connection_type,
                'activity'         => $device->activity,
                'tracking_state'   => $device->tracking_state,
                'activity_status'  => $device->activity_status,
                'speed_kmh'        => $device->speed_kmh,
                'last_seen'        => $device->last_seen?->toIso8601String(),
                'pairing_status'   => $device->pairing_status,
            ],
        ], 200);
    }

    /**
     * Devuelve las zonas seguras del dispositivo asociado al device_token actual.
     *
     * GET /api/v1/device/safe-places
     * Header: Authorization: Bearer <device_token>
     *
     * Más conveniente para el móvil que GET /api/v1/devices/{identifier}/safe-places
     * porque no requiere conocer el identifier.
     */
    public function safePlaces(Request $request): JsonResponse
    {
        $device = $this->resolveDeviceFromToken($request);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado o token inválido.',
            ], 404);
        }

        $safePlaces = $device->safePlaces()->get()->map(function ($place) {
            return [
                'id'        => (string) $place->id,
                'name'      => $place->name,
                'latitude'  => (float) $place->latitude,
                'longitude' => (float) $place->longitude,
                'radius'    => (int) $place->radius_meters,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $safePlaces,
        ], 200);
    }

    /**
     * Crea una zona segura para el dispositivo asociado al device_token actual.
     *
     * POST /api/v1/device/safe-places
     * Header: Authorization: Bearer <device_token>
     * Body: { "name": "Casa", "latitude": 14.064, "longitude": -87.206, "radius": 100 }
     */
    public function storeSafePlace(Request $request): JsonResponse
    {
        $device = $this->resolveDeviceFromToken($request);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado o token inválido.',
            ], 404);
        }

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius'    => ['required', 'integer', 'min:10', 'max:5000'],
        ]);

        $safePlace = $device->safePlaces()->create([
            'name'          => $validated['name'],
            'latitude'      => $validated['latitude'],
            'longitude'     => $validated['longitude'],
            'radius_meters' => $validated['radius'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Zona segura creada correctamente.',
            'data'    => [
                'id'        => (string) $safePlace->id,
                'name'      => $safePlace->name,
                'latitude'  => (float) $safePlace->latitude,
                'longitude' => (float) $safePlace->longitude,
                'radius'    => (int) $safePlace->radius_meters,
            ],
        ], 201);
    }

    /**
     * Resuelve el dispositivo a partir del token Sanctum.
     * Busca el token con prefijo 'device_token:<id>' para identificar
     * qué dispositivo está haciendo la petición.
     */
    private function resolveDeviceFromToken(Request $request): ?Device
    {
        $token = $request->user()->currentAccessToken();

        if (! $token || ! str_contains($token->name, 'device_token:')) {
            return null;
        }

        $deviceId = str_replace('device_token:', '', $token->name);

        return Device::where('user_id', $request->user()->id)->find($deviceId);
    }
}
