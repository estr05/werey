<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Device;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Obtenemos todos los dispositivos registrados por tu App de Flutter
        $devices = Device::all();

        // 2. Calculamos estadísticas en tiempo real para el diseño de Warey
        $stats = [
            'total' => $devices->count(),
            // Un dispositivo se considera 'online' si reportó en los últimos 5 minutos
            'online' => $devices->where('last_seen', '>=', now()->subMinutes(5))->count(),
            // Filtramos los que enviaron actividad 'moving' en tu prueba de 0.2 de velocidad
            'moving' => $devices->where('activity', 'moving')->count(),
            'alerts' => $devices->where('battery_level', '<', 20)->count(),
        ];

        // 3. Enviamos los datos a la vista 'dashboard.blade.php'
        return view('dashboard', compact('devices', 'stats'));
    }
    // ... dentro de DashboardController ...

    public function show($id)
    {
        $device = Device::with(['locationHistories' => function ($query) {
            $query->latest()->limit(50); // Traemos los últimos 50 puntos para la ruta en el mapa
        }])->findOrFail($id);

        return view('device-detail', compact('device'));
    }

    public function destroy($id)
    {
        $device = Device::findOrFail($id);
        $device->delete(); // Esto eliminará también el historial por la cascada en la DB

        return redirect()->route('dashboard')->with('success', 'Dispositivo eliminado');
    }
}
