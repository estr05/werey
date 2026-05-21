<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\LocationHistory;

class DeviceController extends Controller
{
    public function update(Request $request)
    {
        // 1. Actualizar o crear el estado actual del dispositivo (Tabla: devices)
        $device = Device::updateOrCreate(
            ['identifier' => $request->identifier], 
            [
                'alias' => $request->alias,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'battery_level' => $request->battery_level,
                'is_charging' => $request->is_charging,
                'connection_type' => $request->connection_type,
                'activity' => $request->activity,
                'screen_active' => $request->screen_active,
                'last_seen' => now(),
            ]
        );

        // 2. Crear el registro en el historial (Tabla: location_histories)
        // Se incluyen los campos de carga y conexión verificados en tus pruebas
        LocationHistory::create([
            'device_id'       => $device->id,
            'latitude'        => $request->latitude,
            'longitude'       => $request->longitude,
            'battery_level'   => $request->battery_level,
            'is_charging'     => $request->is_charging,
            'connection_type' => $request->connection_type,
            'activity'        => $request->activity,
            'screen_active'   => $request->screen_active,
        ]);

        // 3. Respuesta final única
        return response()->json([
            'status' => 'success',
            'message' => 'Ubicación e historial de Warey actualizados correctamente'
        ], 200);
    }
}