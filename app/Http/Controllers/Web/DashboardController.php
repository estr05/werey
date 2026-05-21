<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SafePlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // 1. Obtenemos solo los dispositivos vinculados al usuario logueado
        $devices = $user->devices;

        // 2. Calculamos estadísticas en tiempo real para Warey
        $stats = [
            'total' => $devices->count(),
            // Se considera 'online' si reportó en los últimos 5 minutos
            'online' => $devices->where('last_seen', '>=', now()->subMinutes(5))->count(),
            'moving' => $devices->where('activity', 'moving')->count(),
            'alerts' => $devices->where('battery_level', '<', 20)->count(),
        ];

        return view('dashboard', compact('devices', 'stats'));
    }

    public function storeDevice(Request $request)
    {
        $user = Auth::user();

        // Validación de límite de 3 dispositivos por usuario
        if ($user->devices()->count() >= 3) {
            return redirect()->route('dashboard')->withErrors([
                'device_limit' => 'Has alcanzado el límite máximo de 3 dispositivos vinculados.'
            ]);
        }

        $request->validate([
            'alias' => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string', 'max:255', 'unique:devices,identifier'],
        ]);

        // Crear dispositivo en estado de desarrollo
        $user->devices()->create([
            'alias' => $request->alias,
            'identifier' => $request->identifier,
            'latitude' => 19.4326, // Coordenadas default (Ej. CDMX)
            'longitude' => -99.1332,
            'battery_level' => 100,
            'is_charging' => false,
            'connection_type' => 'wifi',
            'activity' => 'still',
            'screen_active' => false,
            'last_seen' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Dispositivo vinculado con éxito.');
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();
        
        // Buscamos el dispositivo del usuario logueado
        $device = $user->devices()->findOrFail($id);

        // Buscar fecha seleccionada o usar la del último registro
        $lastPoint = $device->locationHistories()->latest()->first();
        $defaultDate = $lastPoint ? $lastPoint->created_at->toDateString() : now()->toDateString();
        $selectedDate = $request->query('date', $defaultDate);

        // Cargamos el historial de ubicaciones para esa fecha y los lugares seguros
        $locationHistories = $device->locationHistories()
            ->whereDate('created_at', $selectedDate)
            ->orderBy('created_at', 'asc')
            ->get();

        $safePlaces = $device->safePlaces;

        // Validar si el dispositivo se encuentra actualmente dentro de alguna zona segura
        $isInsideSafeZone = false;
        $activeSafeZoneName = null;
        
        if ($safePlaces->count() > 0 && $device->latitude && $device->longitude) {
            foreach ($safePlaces as $place) {
                $earthRadius = 6371000; // metros
                $latFrom = deg2rad($device->latitude);
                $lonFrom = deg2rad($device->longitude);
                $latTo = deg2rad($place->latitude);
                $lonTo = deg2rad($place->longitude);

                $latDelta = $latTo - $latFrom;
                $lonDelta = $lonTo - $lonFrom;

                $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
                $distance = $angle * $earthRadius;

                if ($distance <= $place->radius_meters) {
                    $isInsideSafeZone = true;
                    $activeSafeZoneName = $place->name;
                    break;
                }
            }
        }

        return view('device-detail', compact(
            'device', 
            'locationHistories', 
            'safePlaces', 
            'selectedDate', 
            'isInsideSafeZone', 
            'activeSafeZoneName'
        ));
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $device = $user->devices()->findOrFail($id);
        $device->delete(); // Cascada en DB eliminará historial y lugares seguros

        return redirect()->route('dashboard')->with('success', 'Dispositivo desvinculado.');
    }

    public function storeSafePlace(Request $request, $id)
    {
        $user = Auth::user();
        $device = $user->devices()->findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
        ]);

        $device->safePlaces()->create([
            'name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->radius_meters,
        ]);

        return redirect()->route('device.show', $device->id)->with('success', 'Punto seguro agregado correctamente.');
    }

    public function destroySafePlace($id)
    {
        $safePlace = SafePlace::findOrFail($id);
        
        // Verificar que pertenece a un dispositivo del usuario logueado
        $device = Auth::user()->devices()->findOrFail($safePlace->device_id);

        $safePlace->delete();

        return redirect()->route('device.show', $device->id)->with('success', 'Punto seguro eliminado.');
    }
}
