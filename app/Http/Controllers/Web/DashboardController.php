<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SafePlace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // 1. Obtenemos solo los dispositivos vinculados al usuario logueado
        $devices = $user->devices;

        // 2. Calculamos estadísticas — solo dispositivos que ya han enviado telemetría real
        $stats = [
            'total'  => $devices->count(),
            'online' => $devices->whereNotNull('last_seen')->where('last_seen', '>=', now()->subMinutes(5))->count(),
            'moving' => $devices->where('activity', 'moving')->count(),
            'alerts' => $devices->whereNotNull('battery_level')->where('battery_level', '<', 20)->count(),
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
            'alias'      => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        // Verificar si el identificador ya existe en la base de datos
        $existing = Device::where('identifier', $request->identifier)->first();

        if ($existing) {
            if ($existing->user_id === $user->id) {
                // El código ya pertenece a este usuario: simplemente actualizar alias
                $existing->update(['alias' => $request->alias]);
                return redirect()->route('dashboard')->with('success', 'Dispositivo actualizado correctamente.');
            }
            // El código ya está en uso por otra cuenta
            return redirect()->route('dashboard')->withErrors([
                'identifier' => 'Este código ya está siendo utilizado por otra cuenta.'
            ]);
        }

        // Crear dispositivo SIN datos mock — en espera de primera telemetría real
        $user->devices()->create([
            'alias'      => $request->alias,
            'identifier' => $request->identifier,
        ]);

        return redirect()->route('dashboard')->with('success', '¡Dispositivo registrado! Esperando vinculación desde la app móvil.');
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

    /**
     * Endpoint JSON ligero para auto-refresh del dashboard.
     * Devuelve solo los datos necesarios (dispositivos + stats) sin renderizar HTML.
     * GET /dashboard/json
     */
    public function jsonDevices(): JsonResponse
    {
        $user = Auth::user();
        $devices = $user->devices;

        $stats = [
            'total'  => $devices->count(),
            'online' => $devices->whereNotNull('last_seen')->where('last_seen', '>=', now()->subMinutes(5))->count(),
            'moving' => $devices->where('activity', 'moving')->count(),
            'alerts' => $devices->whereNotNull('battery_level')->where('battery_level', '<', 20)->count(),
        ];

        $data = $devices->map(function ($device) {
            $isPending = is_null($device->last_seen);
            return [
                'id'              => $device->id,
                'alias'           => $device->alias,
                'identifier'      => $device->identifier,
                'is_pending'      => $isPending,
                'activity'        => $device->activity ?? 'unknown',
                'battery_level'   => $device->battery_level,
                'is_charging'     => $device->is_charging,
                'connection_type' => $device->connection_type,
                'last_seen'       => $device->last_seen?->diffForHumans(),
                'last_seen_raw'   => $device->last_seen?->toIso8601String(),
                'latitude'        => $device->latitude,
                'longitude'       => $device->longitude,
                'show_url'        => route('device.show', $device->id),
                'delete_url'      => route('device.destroy', $device->id),
            ];
        });

        return response()->json([
            'success' => true,
            'stats'   => $stats,
            'devices' => $data,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Endpoint SSE (Server-Sent Events) para actualizaciones en tiempo real del dashboard.
     * Reemplaza el polling HTTP manteniendo una conexión persistente.
     * GET /dashboard/sse
     *
     * El cliente se conecta via EventSource y recibe:
     *   - event: update  → payload con stats + devices
     *   - event: heartbeat → mantiene la conexión activa
     */
    public function sseStream(): Response
    {
        $user = Auth::user();

        // Liberar la sesión antes de entrar al loop para no bloquear otras requests del mismo usuario
        session_write_close();

        $response = new StreamedResponse(function () use ($user) {
            // Deshabilitar el buffer de salida para que los datos se envíen inmediatamente
            if (ob_get_level()) {
                ob_end_clean();
            }

            $lastPayload = null;

            while (!connection_aborted()) {
                // Refrescar el usuario desde la BD en cada iteración
                $user->refresh();
                $devices = $user->devices;

                $stats = [
                    'total'  => $devices->count(),
                    'online' => $devices->whereNotNull('last_seen')->where('last_seen', '>=', now()->subMinutes(5))->count(),
                    'moving' => $devices->where('activity', 'moving')->count(),
                    'alerts' => $devices->whereNotNull('battery_level')->where('battery_level', '<', 20)->count(),
                ];

                $data = $devices->map(function ($device) {
                    $isPending = is_null($device->last_seen);
                    return [
                        'id'              => $device->id,
                        'alias'           => $device->alias,
                        'identifier'      => $device->identifier,
                        'is_pending'      => $isPending,
                        'activity'        => $device->activity ?? 'unknown',
                        'battery_level'   => $device->battery_level,
                        'is_charging'     => $device->is_charging,
                        'connection_type' => $device->connection_type,
                        'last_seen'       => $device->last_seen?->diffForHumans(),
                        'last_seen_raw'   => $device->last_seen?->toIso8601String(),
                        'latitude'        => $device->latitude,
                        'longitude'       => $device->longitude,
                        'show_url'        => route('device.show', $device->id),
                        'delete_url'      => route('device.destroy', $device->id),
                    ];
                });

                $payload = json_encode([
                    'success' => true,
                    'stats'   => $stats,
                    'devices' => $data,
                    'server_time' => now()->toIso8601String(),
                ]);

                // Solo enviar si los datos cambiaron (evita tráfico innecesario)
                if ($payload !== $lastPayload) {
                    echo "event: update\n";
                    echo "data: {$payload}\n\n";
                    $lastPayload = $payload;
                } else {
                    // Heartbeat para mantener la conexión viva
                    echo "event: heartbeat\n";
                    echo "data: {\"time\":\"" . now()->toIso8601String() . "\"}\n\n";
                }

                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                // Esperar 3 segundos antes del siguiente ciclo
                sleep(3);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('X-Accel-Buffering', 'no'); // Deshabilitar buffering de nginx

        return $response;
    }
}
