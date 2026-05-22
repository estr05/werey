<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandshakeController extends Controller
{
    /**
     * Vincula un dispositivo físico (por su UUID de hardware) usando el
     * código generado en el panel web (WRY-XXXX-XXXX).
     *
     * POST /api/v1/devices/handshake
     */
    public function pair(Request $request): JsonResponse
    {
        $request->validate([
            'pairing_code' => ['required', 'string'],
            'device_uuid'  => ['required', 'string', 'max:255'],
        ]);

        // Buscamos el dispositivo por el identificador (que en el dashboard generamos como WRY-XXXX-XXXX)
        $device = Device::where('identifier', $request->pairing_code)->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'El código de vinculación no es válido o ha expirado.',
            ], 404);
        }

        // Obtener el usuario dueño de este dispositivo
        $user = $device->user;
        
        // Generar un token único para este hardware
        $tokenName = "device_token:{$request->device_uuid}";
        
        // Borrar tokens anteriores de este mismo hardware (opcional, buena práctica)
        $user->tokens()->where('name', $tokenName)->delete();
        
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo vinculado y activado correctamente.',
            'data'    => [
                'token' => $token,
            ],
        ], 200);
    }
}
