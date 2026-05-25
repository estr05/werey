<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SafePlace;
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
            return $this->formatSafePlace($place);
        });

        return response()->json([
            'success' => true,
            'data'    => $safePlaces,
        ]);
    }

    /**
     * Crea una nueva zona segura para un dispositivo específico.
     *
     * POST /api/v1/devices/{identifier}/safe-places
     * Header: Authorization: Bearer <token>
     * Body: { "name": "Casa", "latitude": 14.064, "longitude": -87.206, "radius": 100 }
     */
    public function store(Request $request, string $identifier): JsonResponse
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
            'data'    => $this->formatSafePlace($safePlace),
        ], 201);
    }

    /**
     * Elimina una zona segura por su ID.
     *
     * DELETE /api/v1/safe-places/{id}
     * Header: Authorization: Bearer <token>
     *
     * Verifica que la zona segura pertenezca a un dispositivo del usuario autenticado.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();

        $safePlace = SafePlace::find($id);

        if (! $safePlace) {
            return response()->json([
                'success' => false,
                'message' => 'Zona segura no encontrada.',
            ], 404);
        }

        // Verificar que la zona pertenece a un dispositivo del usuario
        $device = $user->devices()->find($safePlace->device_id);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para eliminar esta zona segura.',
            ], 403);
        }

        $safePlace->delete();

        return response()->json([
            'success' => true,
            'message' => 'Zona segura eliminada correctamente.',
        ], 200);
    }

    /**
     * Formatea un SafePlace para respuesta JSON estandarizada.
     * El móvil espera: id (string), name, latitude, longitude, radius (int)
     */
    private function formatSafePlace($place): array
    {
        return [
            'id'        => (string) $place->id,
            'name'      => $place->name,
            'latitude'  => (float) $place->latitude,
            'longitude' => (float) $place->longitude,
            'radius'    => (int) $place->radius_meters,
        ];
    }
}
