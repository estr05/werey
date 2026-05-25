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

        // Verificar si el código de emparejamiento ya existe y está activo
        $existing = Device::where('pairing_code', $request->identifier)->first();

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
        // Generar el código como pairing_code, el identifier lo hacemos permanente
        $pairingCode = $request->identifier; // El frontend envía WRY-XXXX-XXXX
        
        $user->devices()->create([
            'alias'              => $request->alias,
            'identifier'         => \Illuminate\Support\Str::uuid(), // ID interno permanente (Route Key)
            'pairing_code'       => $pairingCode,
            'pairing_status'     => 'pending',
            'pairing_expires_at' => now()->addMinutes(15),
        ]);

        return redirect()->route('dashboard')->with('success', '¡Dispositivo registrado! Esperando vinculación desde la app móvil con el código generado.');
    }

    public function show(Request $request, Device $device)
    {
        if ($device->user_id !== Auth::id()) {
            abort(403, 'No autorizado.');
        }

        // Buscar fecha seleccionada o usar la del último registro
        $lastPoint = $device->locationHistories()->orderBy('captured_at', 'desc')->first();
        $defaultDate = ($lastPoint && $lastPoint->captured_at) 
            ? $lastPoint->captured_at->toDateString() 
            : ($lastPoint ? $lastPoint->created_at->toDateString() : now()->toDateString());
        $selectedDate = $request->query('date', $defaultDate);

        // El historial se carga por API JSON en T5 para el mapa,
        // pero la vista Blade todavía usa $locationHistories para mostrar la lista de 'Movimientos de Hoy'
        $locationHistories = $device->locationHistories()
            ->whereDate('captured_at', $selectedDate)
            ->orderBy('captured_at', 'asc')
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

        $availableDates = $device->locationHistories()
            ->selectRaw('DATE(COALESCE(captured_at, created_at)) as date')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->pluck('date');

        return view('device-detail', compact(
            'device', 
            'locationHistories',
            'safePlaces', 
            'selectedDate',
            'availableDates',
            'isInsideSafeZone', 
            'activeSafeZoneName'
        ));
    }

    public function destroy(Device $device)
    {
        if ($device->user_id !== Auth::id()) {
            abort(403, 'No autorizado.');
        }
        $device->delete(); // Cascada en DB eliminará historial y lugares seguros

        return redirect()->route('dashboard')->with('success', 'Dispositivo desvinculado.');
    }

    public function storeSafePlace(Request $request, Device $device)
    {
        if ($device->user_id !== Auth::id()) {
            abort(403, 'No autorizado.');
        }

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

        return redirect()->route('device.show', $device)->with('success', 'Punto seguro agregado correctamente.');
    }

    public function destroySafePlace($id)
    {
        $safePlace = SafePlace::findOrFail($id);
        
        // Verificar que pertenece a un dispositivo del usuario logueado
        $device = Auth::user()->devices()->findOrFail($safePlace->device_id);

        $safePlace->delete();

        return redirect()->route('device.show', $device)->with('success', 'Punto seguro eliminado.');
    }

    /**
     * T4 — Historial GPS paginado por fecha.
     * Devuelve los puntos de ubicación de un dispositivo para una fecha dada.
     * Protegido por sesión web (guard 'auth') — el browser envía la cookie automáticamente.
     * GET /device/{id}/history?date=YYYY-MM-DD
     */
    public function historyJson(Request $request, Device $device): JsonResponse
    {
        if ($device->user_id !== Auth::id()) {
            abort(403, 'No autorizado.');
        }

        $date  = $request->query('date', now()->toDateString());
        $limit = min((int) $request->query('limit', 500), 1000);

        $points = $device->locationHistories()
            ->whereDate('captured_at', $date)
            ->orderBy('captured_at', 'asc')
            ->limit($limit)
            ->get([
                'latitude', 'longitude', 'activity', 'movement_type',
                'speed_kmh', 'battery_level', 'is_charging', 'screen_active',
                'intervalo_aplicado', 'motivo', 'created_at', 'captured_at', 'bearing', 'accuracy'
            ])
            ->map(fn ($p) => [
                'lat'            => (float) $p->latitude,
                'lng'            => (float) $p->longitude,
                'activity'       => $p->activity ?? 'still',
                'movement_type'  => $p->movement_type ?? 'STATIC',
                'speed_kmh'      => $p->speed_kmh ? (float) $p->speed_kmh : null,
                'battery'        => $p->battery_level,
                'is_charging'    => (bool) $p->is_charging,
                'screen_active'  => (bool) $p->screen_active,
                'intervalo'      => $p->intervalo_aplicado,
                'motivo'         => $p->motivo,
                'bearing'        => $p->bearing,
                'cardinal'       => $p->cardinal_direction,
                'accuracy'       => $p->accuracy,
                'time'           => ($p->captured_at ?? $p->created_at)->toIso8601String(),
                'label_time'     => ($p->captured_at ?? $p->created_at)->format('H:i:s'),
            ]);

        return response()->json([
            'success' => true,
            'date'    => $date,
            'total'   => $points->count(),
            'points'  => $points,
        ]);
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
                'id'               => $device->id,
                'alias'            => $device->alias,
                'identifier'       => $device->identifier,
                'pairing_code'     => $device->pairing_code,
                'pairing_status'   => $device->pairing_status,
                'is_pending'       => $isPending,
                'activity'         => $device->activity ?? 'unknown',
                'battery_level'    => $device->battery_level,
                'is_charging'      => $device->is_charging,
                'connection_type'  => $device->connection_type,
                'signal_strength'  => $device->signal_strength,
                'has_internet'     => $device->has_internet,
                'tracking_state'   => $device->tracking_state,
                'activity_status'  => $device->activity_status,
                'speed_kmh'        => $device->speed_kmh,
                'intervalo_aplicado' => $device->intervalo_aplicado,
                'motivo'           => $device->motivo,
                'last_seen'        => $device->last_seen?->diffForHumans(),
                'last_seen_raw'    => $device->last_seen?->toIso8601String(),
                'latitude'         => $device->latitude,
                'longitude'        => $device->longitude,
                'show_url'         => route('device.show', $device),
                'delete_url'       => route('device.destroy', $device),
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
    public function sseStream(): StreamedResponse
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
                        'id'               => $device->id,
                        'alias'            => $device->alias,
                        'identifier'       => $device->identifier,
                        'pairing_code'     => $device->pairing_code,
                        'pairing_status'   => $device->pairing_status,
                        'is_pending'       => $isPending,
                        'activity'         => $device->activity ?? 'unknown',
                        'battery_level'    => $device->battery_level,
                        'is_charging'      => $device->is_charging,
                        'connection_type'  => $device->connection_type,
                        'signal_strength'  => $device->signal_strength,
                        'has_internet'     => $device->has_internet,
                        'tracking_state'   => $device->tracking_state,
                        'activity_status'  => $device->activity_status,
                        'speed_kmh'        => $device->speed_kmh,
                        'intervalo_aplicado' => $device->intervalo_aplicado,
                        'motivo'           => $device->motivo,
                        'last_seen'        => $device->last_seen?->diffForHumans(),
                        'last_seen_raw'    => $device->last_seen?->toIso8601String(),
                        'latitude'         => $device->latitude,
                        'longitude'        => $device->longitude,
                        'show_url'         => route('device.show', $device),
                        'delete_url'       => route('device.destroy', $device),
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

    /**
     * T7-backend — SSE de posición en tiempo real para la vista del dispositivo.
     * Emite evento 'position' cuando lat/lng cambia, 'heartbeat' cuando no hay cambios.
     * GET /device/{id}/sse
     */
    public function deviceSseStream(Request $request, Device $device): StreamedResponse
    {
        if ($device->user_id !== Auth::id()) {
            abort(403, 'No autorizado.');
        }
        session_write_close();

        $response = new StreamedResponse(function () use ($device) {
            if (ob_get_level()) {
                ob_end_clean();
            }

            $lastLat     = null;
            $lastLng     = null;
            $lastSeenRaw = null;

            while (!connection_aborted()) {
                $device->refresh();

                $changed = ($device->latitude  !== $lastLat)
                        || ($device->longitude !== $lastLng)
                        || ($device->last_seen?->toIso8601String() !== $lastSeenRaw);

                if ($changed) {
                    $payload = json_encode([
                        'latitude'    => $device->latitude ? (float) $device->latitude : null,
                        'longitude'   => $device->longitude ? (float) $device->longitude : null,
                        'bearing'     => $device->bearing ? (float) $device->bearing : null,
                        'speed_kmh'   => $device->speed_kmh ? (float) $device->speed_kmh : null,
                        'activity'    => $device->activity ?? 'still',
                        'movement_type' => $device->motivo ?? null,
                        'battery'     => $device->battery_level,
                        'is_charging' => (bool) $device->is_charging,
                        'last_seen'   => $device->last_seen?->diffForHumans(),
                        'server_time' => now()->toIso8601String(),
                    ]);

                    echo "event: position\n";
                    echo "data: {$payload}\n\n";

                    $lastLat     = $device->latitude;
                    $lastLng     = $device->longitude;
                    $lastSeenRaw = $device->last_seen?->toIso8601String();
                } else {
                    echo "event: heartbeat\n";
                    echo 'data: {"time":"' . now()->toIso8601String() . '"}' . "\n\n";
                }

                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                sleep(3);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
