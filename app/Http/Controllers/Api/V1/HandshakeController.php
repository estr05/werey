<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DevicePairingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandshakeController extends Controller
{
    /**
     * Vincula un dispositivo físico (por su UUID de hardware) usando el
     * código generado en el panel web (WRY-XXXX-XXXX).
     *
     * POST /api/v1/devices/handshake
     *
     * La app móvil envía los campos del fingerprint en camelCase:
     *   { pairing_code, deviceUuid, model, manufacturer, android_version,
     *     app_version, device_fingerprint: {...}, confirm_replacement }
     * Este controlador normaliza todo a snake_case antes de validar.
     */
    public function pair(Request $request): JsonResponse
    {
        // ── 0. Normalizar camelCase/alias → snake_case ───────────────────────
        // La app móvil (warey_movil) envía estos campos desde
        // HandshakeRemoteDataSource con los nombres del DeviceFingerprint.

        // Extraer campos del objeto device_fingerprint anidado como fallback
        $fingerprint = $request->input('device_fingerprint', []);
        if (is_array($fingerprint)) {
            foreach ($fingerprint as $key => $value) {
                if (!$request->has($key)) {
                    $request->merge([$key => $value]);
                }
            }
        }

        $request->merge([
            // Mapeo de camelCase que envía DeviceFingerprint.toJson()
            'device_uuid'         => $request->input('device_uuid', $request->input('deviceUuid')),
            'device_manufacturer' => $request->input('device_manufacturer', $request->input('manufacturer')),
            'device_model'        => $request->input('device_model', $request->input('model')),
            'os_version'          => $request->input('os_version', $request->input('android_version')),
            'app_version'         => $request->input('app_version'),
            // confirm_replacement → force (la app móvil usa confirm_replacement)
            'force'               => $request->input('force', $request->input('confirm_replacement')),
        ]);

        $request->validate([
            'pairing_code'        => ['required', 'string'],
            'device_uuid'         => ['required', 'string', 'max:255'],
            'device_manufacturer' => ['nullable', 'string', 'max:255'],
            'device_model'        => ['nullable', 'string', 'max:255'],
            'os_version'          => ['nullable', 'string', 'max:255'],
            'app_version'         => ['nullable', 'string', 'max:255'],
            'force'               => ['nullable', 'boolean'],
        ]);

        $pairingCode = $request->pairing_code;
        $deviceUuid = $request->device_uuid;
        $force = $request->boolean('force', false);

        // Buscar el dispositivo por el pairing_code que se generó y aún está vigente
        // Si no se encuentra, tal vez el código expiró o es inválido
        $device = Device::where('pairing_code', $pairingCode)
                        ->where('pairing_status', 'pending')
                        ->where(function($q) {
                            $q->whereNull('pairing_expires_at')
                              ->orWhere('pairing_expires_at', '>', now());
                        })
                        ->first();

        if (!$device) {
            // Registrar intento fallido genérico si se quiere (requeriría buscar por pairing_code sin restricciones para hallar a qué device apuntaba)
            $expiredDevice = Device::where('pairing_code', $pairingCode)->first();
            if ($expiredDevice) {
                DevicePairingEvent::create([
                    'device_id' => $expiredDevice->id,
                    'event_type' => 'failed_attempt',
                    'new_device_uuid' => $deviceUuid,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => ['reason' => 'pairing code expired or not pending']
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'El código de vinculación no es válido o ha expirado.',
            ], 404);
        }

        // Obtener el usuario dueño de este dispositivo
        $user = $device->user;

        // Detección de conflicto (prevención de reemplazo silencioso)
        if ($device->device_uuid && $device->device_uuid !== $deviceUuid) {
            if (!$force) {
                // Registrar intento de reemplazo
                DevicePairingEvent::create([
                    'device_id' => $device->id,
                    'event_type' => 'replacement_requested',
                    'previous_device_uuid' => $device->device_uuid,
                    'new_device_uuid' => $deviceUuid,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'success' => false,
                    'requires_confirmation' => true,
                    'message' => "Este perfil ya tiene un dispositivo vinculado ({$device->device_manufacturer} {$device->device_model}). ¿Deseas reemplazarlo?"
                ], 409);
            }

            // Si llegamos aquí, force=true, se procede al reemplazo
            $eventType = 'replaced';
            $previousUuid = $device->device_uuid;
            
            // Borrar tokens anteriores de este mismo hardware
            $tokenName = "device_token:{$device->id}";
            $user->tokens()->where('name', $tokenName)->delete();
        } else {
            $eventType = 'paired';
            $previousUuid = null;
        }

        // Actualizar dispositivo con la nueva info
        $device->update([
            'device_uuid' => $deviceUuid,
            'device_manufacturer' => $request->device_manufacturer,
            'device_model' => $request->device_model,
            'os_version' => $request->os_version,
            'app_version' => $request->app_version,
            'pairing_status' => 'paired',
            'pairing_code' => null, // El código es de un solo uso
            'pairing_expires_at' => null,
        ]);

        // Registrar el evento final
        DevicePairingEvent::create([
            'device_id' => $device->id,
            'event_type' => $eventType,
            'previous_device_uuid' => $previousUuid,
            'new_device_uuid' => $deviceUuid,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'model' => $request->device_model,
                'os' => $request->os_version,
                'app' => $request->app_version,
            ]
        ]);
        
        $tokenName = "device_token:{$device->id}";
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo vinculado y activado correctamente.',
            'data'    => [
                'token'             => $token,
                'device_identifier' => $device->identifier,
                'device'            => [
                    'alias'            => $device->alias,
                    'paired_at'        => $device->updated_at?->toIso8601String(),
                    'last_seen'        => $device->last_seen?->toIso8601String(),
                    'status'           => $device->pairing_status,
                    'model'            => $request->device_model,
                    'manufacturer'     => $request->device_manufacturer,
                    'android_version'  => $request->os_version,
                    'app_version'      => $request->app_version,
                    'fingerprint'      => $deviceUuid,
                ],
            ],
        ], 200);
    }
}
