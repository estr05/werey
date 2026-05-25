<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\LocationHistory;
use App\Services\SpatialFilter;
use App\Services\TelemetryHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        // Normalizar camelCase → snake_case para compatibilidad con la app móvil
        $request->merge([
            'movement_type'      => $request->input('movement_type'),
            'smoothed_speed'     => $request->input('smoothed_speed'),
            // captured_at mantiene fallback a created_at por si hay
            // frames antiguos en la cola offline que usen ese nombre.
            'captured_at'        => $request->input('captured_at', $request->input('created_at')),
            'speed_kmh'          => $request->input('speed_kmh'),
            'intervalo_aplicado' => $request->input('intervalo_aplicado'),
            'is_safe_zone'       => $request->input('is_safe_zone'),
            'zone_name'          => $request->input('zone_name'),
        ]);

        $validated = $request->validate([
            'latitude'           => ['required', 'numeric', 'between:-90,90'],
            'longitude'          => ['required', 'numeric', 'between:-180,180'],
            'accuracy'           => ['nullable', 'numeric'],
            'speed'              => ['nullable', 'numeric'],
            'smoothed_speed'     => ['nullable', 'numeric'],
            'altitude'           => ['nullable', 'numeric'],
            'movement_type'      => ['nullable', 'string', 'in:STATIC,WALKING,RUNNING,VEHICLE'],
            'tracking_state'     => ['nullable', 'string'],
            'is_safe_zone'       => ['nullable', 'boolean'],
            'zone_name'          => ['nullable', 'string'],
            'captured_at'        => ['nullable', 'string'],
            'speed_kmh'          => ['nullable', 'numeric'],
            'intervalo_aplicado' => ['nullable', 'integer'],
            'motivo'             => ['nullable', 'string'],
            'bearing'            => ['nullable', 'numeric'],
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

        // Calcular speed_kmh, intervalo_aplicado, motivo y bearing si no se envían
        $helper = new TelemetryHelper();
        $speedKmh = $helper->calculateSpeedKmh(
            $validated['speed_kmh'] ?? null,
            $validated['smoothed_speed'] ?? null,
            $validated['speed'] ?? null
        );

        $isSafe = $validated['is_safe_zone'] ?? true;
        $intervalo = $validated['intervalo_aplicado'] ?? $helper->calculateInterval($speedKmh, $isSafe);
        $motivo = $validated['motivo'] ?? $helper->calculateMotivo($speedKmh, $isSafe);
        $bearing = $helper->processBearing(
            $validated['bearing'] ?? null,
            $device->bearing,
            $speedKmh
        );

        Log::info('[Location] Frame GPS recibido', [
            'device_id'          => $device->id,
            'latitude'           => $validated['latitude'],
            'longitude'          => $validated['longitude'],
            'movement_type'      => $movementType,
            'accuracy'           => $validated['accuracy'] ?? null,
            'bearing'            => $bearing,
            'speed_kmh'          => $speedKmh,
            'intervalo_aplicado' => $intervalo,
            'motivo'             => $motivo,
        ]);

        // 4. Filtrado Espacial Inteligente (SpatialFilter)
        $spatialData = [];
        $filter = new SpatialFilter();
        $spatialData = $filter->process($device, $validated);

        if ($spatialData['is_outlier'] ?? false) {
            // Es basura, no actualizamos la ubicación actual, pero lo guardamos en historial como outlier?
            // Para simplificar, ignoramos la actualización principal de posición si es outlier.
            Log::info('[Location] Frame descartado por SpatialFilter', ['device_id' => $device->id]);
        } else {
            // Actualizamos las coordenadas con las suavizadas
            $validated['latitude'] = $spatialData['latitude'];
            $validated['longitude'] = $spatialData['longitude'];
            
            // 1. Actualizar posición y estado actual del dispositivo
            //    También refrescamos last_seen para mantener el dispositivo como "online"
            $device->update([
                'latitude'           => $validated['latitude'],
                'longitude'          => $validated['longitude'],
                'activity'           => strtolower($movementType),
                'speed_kmh'          => $speedKmh,
                'intervalo_aplicado' => $intervalo,
                'motivo'             => $motivo,
                'bearing'            => $bearing,
                'last_seen'          => now(),
            ]);
        }

        // 2. Guardar en historial para el mapa del dashboard
        LocationHistory::create([
            'device_id'          => $device->id,
            'latitude'           => $validated['latitude'],
            'longitude'          => $validated['longitude'],
            'raw_latitude'       => $spatialData['raw_latitude'] ?? $validated['latitude'],
            'raw_longitude'      => $spatialData['raw_longitude'] ?? $validated['longitude'],
            'confidence_score'   => $spatialData['confidence_score'] ?? 100,
            'is_outlier'         => $spatialData['is_outlier'] ?? false,
            'battery_level'      => $device->battery_level,       // Hereda del último device-status
            'is_charging'        => $device->is_charging,         // Hereda del último device-status
            'connection_type'    => $device->connection_type,     // Hereda del último device-status
            'activity'           => strtolower($movementType),
            'movement_type'      => $movementType,
            'screen_active'      => $device->screen_active,       // Hereda del último device-status
            'bearing'            => $bearing,
            'speed_kmh'          => $speedKmh,
            'intervalo_aplicado' => $intervalo,
            'motivo'             => $motivo,
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
