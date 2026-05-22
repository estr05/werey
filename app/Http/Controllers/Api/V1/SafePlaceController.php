<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SafePlaceController extends Controller
{
    /**
     * Devuelve la lista de lugares seguros (zonas de geofencing) de un
     * dispositivo específico, identificado por su código WRY (identifier).
     *
     * GET /api/v1/devices/{identifier}/safe-places
     *
     * El móvil usa estas zonas para configurar el GeofenceService local
     * y evaluar en tiempo real si el dispositivo está dentro de un área segura.
     */
    public function index(string $identifier): JsonResponse
    {
        $user = auth()->user();

        $device = Device::where('identifier', $identifier)
            ->where('user_id', $user->id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado.',
            ], 404);
        }

        $safePlaces = $device->safePlaces()->get()->map(function ($place) {
            return [
                'id'       => (string) $place->id,
                'name'     => $place->name,
                'latitude' => (float) $place->latitude,
                'longitude'=> (float) $place->longitude,
                'radius'   => (int) $place->radius_meters,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $safePlaces,
        ]);
    }
}
