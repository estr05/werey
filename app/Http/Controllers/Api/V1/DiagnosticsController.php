<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\LocationHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DiagnosticsController
 *
 * Endpoints de diagnóstico para depurar problemas de sincronización.
 * Solo accesibles con token de Sanctum del usuario propietario del dispositivo.
 *
 * GET /api/v1/diagnostics/device/{id}
 */
class DiagnosticsController extends Controller
{
    /**
     * Devuelve el estado RAW completo de un dispositivo para diagnóstico.
     *
     * Incluye:
     *   - Todos los campos de la tabla `devices` (incluyendo nulos)
     *   - El usuario propietario
     *   - Token de acceso asociado
     *   - Último frame de ubicación recibido
     *   - Últimos 5 registros de location_histories
     *   - Lugares seguros configurados
     *   - Tiempos: cuándo fue last_seen, diferencia, etc.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Buscar el dispositivo asegurando que pertenezca al usuario autenticado
        $device = $user->devices()->find($id);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado o no pertenece a este usuario.',
            ], 404);
        }

        // --- 1. RAW del modelo Device (todos los campos) ---
        $deviceRaw = $device->toArray();
        $deviceRaw['_fillable'] = $device->getFillable();
        $deviceRaw['_casts']    = $device->getCasts();

        // --- 2. Información del token usado para esta request ---
        $currentToken = $request->user()->currentAccessToken();
        $tokenInfo = null;
        if ($currentToken) {
            $tokenInfo = [
                'id'         => $currentToken->id,
                'name'       => $currentToken->name,
                'abilities'  => $currentToken->abilities,
                'created_at' => $currentToken->created_at?->toIso8601String(),
                'last_used_at' => $currentToken->last_used_at?->toIso8601String(),
            ];
        }

        // --- 3. Último frame de location (location_histories más reciente) ---
        $lastLocation = $device->locationHistories()->latest()->first();

        // --- 4. Últimos 5 registros de location_histories ---
        $recentHistory = $device->locationHistories()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->toArray();

        // --- 5. Lugares seguros ---
        $safePlaces = $device->safePlaces()->get()->toArray();

        // --- 6. Información temporal ---
        $now = now();
        $timingInfo = [
            'server_time'         => $now->toIso8601String(),
            'device_last_seen'    => $device->last_seen?->toIso8601String(),
            'last_seen_diff'      => $device->last_seen ? $device->last_seen->diffForHumans() : 'NUNCA',
            'last_seen_minutes_ago' => $device->last_seen ? $now->diffInMinutes($device->last_seen) : null,
            'last_status_at'      => $device->last_status_at?->toIso8601String(),
            'is_online'           => $device->last_seen && $device->last_seen->gte($now->subMinutes(5)),
        ];

        return response()->json([
            'success'    => true,
            'diagnostic' => [
                'device'       => $deviceRaw,
                'token'        => $tokenInfo,
                'last_location_frame' => $lastLocation?->toArray(),
                'recent_history'       => $recentHistory,
                'safe_places'          => $safePlaces,
                'timing'               => $timingInfo,
                '_total_history_count' => $device->locationHistories()->count(),
            ],
        ], 200);
    }
}
