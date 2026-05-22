<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceApiController extends Controller
{
    /**
     * Lista todos los dispositivos vinculados al usuario autenticado.
     *
     * GET /api/v1/devices
     * Header: Authorization: Bearer <token>
     */
    public function index(Request $request): JsonResponse
    {
        $devices = $request->user()
            ->devices()
            ->select([
                'id', 'alias', 'identifier', 'latitude', 'longitude',
                'battery_level', 'is_charging', 'connection_type',
                'activity', 'screen_active', 'last_seen',
            ])
            ->orderByDesc('last_seen')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $devices,
        ], 200);
    }

    /**
     * Devuelve el detalle de un dispositivo específico del usuario autenticado.
     *
     * GET /api/v1/devices/{identifier}
     * Header: Authorization: Bearer <token>
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        $device = $request->user()
            ->devices()
            ->where('identifier', $identifier)
            ->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $device,
        ], 200);
    }
}
