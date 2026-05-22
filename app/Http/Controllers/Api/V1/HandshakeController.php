<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandshakeController extends Controller
{
    /**
     * Vincula un dispositivo físico al usuario autenticado.
     * Si el dispositivo ya existe, lo re-vincula al usuario actual.
     * Si es nuevo, lo crea y lo vincula.
     *
     * POST /api/v1/handshake
     * Header: Authorization: Bearer <token>
     * Body: { "identifier": "uuid-del-dispositivo", "alias": "Mi iPhone" }
     */
    public function pair(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'alias'      => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        // Buscar si el dispositivo ya existe por su UUID
        $device = Device::where('identifier', $request->identifier)->first();

        if ($device) {
            // El dispositivo ya existe: actualizamos alias y lo vinculamos al usuario
            $device->update([
                'user_id' => $user->id,
                'alias'   => $request->alias,
            ]);
            $message = 'Dispositivo re-vinculado correctamente.';
        } else {
            // Dispositivo nuevo: lo creamos y vinculamos al usuario
            $device = Device::create([
                'user_id'    => $user->id,
                'identifier' => $request->identifier,
                'alias'      => $request->alias,
            ]);
            $message = 'Dispositivo registrado y vinculado correctamente.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => [
                'device_id'  => $device->id,
                'identifier' => $device->identifier,
                'alias'      => $device->alias,
                'user_id'    => $device->user_id,
            ],
        ], 200);
    }
}
