<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warey | Unit Control Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: 'Epilogue', sans-serif; }
        /* El mapa ahora ocupa todo el alto disponible del contenedor central */
        #map { height: 100%; width: 100%; border-radius: 24px; z-index: 0; }
        .leaflet-container { background: #0a0c0d !important; }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(2.5); opacity: 0; }
        }
        
        .pulse-yellow {
            animation: pulse-yellow-key 2s infinite;
        }
        
        @keyframes pulse-yellow-key {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(2.2); opacity: 0; }
        }
        
        /* Personalización de scrollbar para las columnas laterales */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #131416; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>

<body class="bg-[#131416] text-slate-100 h-screen overflow-hidden flex flex-col">
    
    <div class="px-6 pt-6 pb-2 shrink-0">
        <header class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="bg-slate-800 p-2 rounded-lg hover:bg-slate-700 transition-all">
                    <span class="material-symbols-outlined text-white">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-2xl font-black text-white italic uppercase tracking-tighter">Unit Control Center</h1>
                    <p class="text-[#8dc3ce] text-[10px] font-bold tracking-[0.3em] uppercase">Rastreo de Telemetría en Tiempo Real</p>
                </div>
            </div>
            <div class="bg-primary/10 border border-primary/20 px-4 py-2 rounded-full">
                <span class="text-[10px] font-mono text-emerald-500 animate-pulse">● ENCRYPTED LINK ACTIVE</span>
            </div>
        </header>
    </div>

    <div class="flex-1 px-6 pb-6 min-h-0">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-full">
            
            <!-- Columna Izquierda: Telemetría actual y Estatus -->
            <div class="lg:col-span-3 flex flex-col gap-4 h-full overflow-y-auto pr-2">
                <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-emerald-500/10 p-2 rounded-lg text-emerald-500">
                                <span class="material-symbols-outlined">api</span>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase font-bold">Actividad</p>
                                <p class="text-white font-bold tracking-wider">{{ strtoupper($device->activity) }}</p>
                            </div>
                        </div>
                        <span class="size-2.5 rounded-full {{ $device->last_seen && $device->last_seen->gt(now()->subMinutes(5)) ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600' }}"></span>
                    </div>
                    
                    <h2 class="text-4xl font-black text-white mb-1">{{ $device->battery_level }}<span class="text-lg text-slate-500">%</span></h2>
                    <p class="text-[10px] text-slate-400 mb-4">Cargando: {{ $device->is_charging ? 'SÍ' : 'NO' }}</p>
                    <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden mb-6">
                        <div class="h-full {{ $device->battery_level > 20 ? 'bg-emerald-500' : 'bg-red-500' }}" style="width: {{ $device->battery_level }}%"></div>
                    </div>

                    <div class="bg-slate-800/50 p-4 rounded-xl border border-white/5 mb-4">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-[10px] text-slate-400 uppercase">Tipo de Conexión</span>
                            <span class="bg-blue-500/20 text-blue-400 text-[9px] px-1.5 py-0.5 rounded font-bold">{{ strtoupper($device->connection_type ?? 'N/A') }}</span>
                        </div>

                        {{-- Señal --}}
                        @if($device->signal_strength !== null)
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-slate-500 uppercase">Señal</span>
                            <div class="flex items-end gap-0.5">
                                @for($i = 0; $i < 4; $i++)
                                    <span class="w-1.5 rounded-sm {{ $i < $device->signal_strength ? 'bg-[#6CD400]' : 'bg-slate-700' }}" style="height: {{ 8 + $i * 5 }}px"></span>
                                @endfor
                            </div>
                        </div>
                        @endif

                        {{-- Internet --}}
                        @if($device->has_internet !== null)
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-slate-500 uppercase">Internet</span>
                            <span class="text-[10px] font-mono {{ $device->has_internet ? 'text-emerald-400 bg-emerald-500/10' : 'text-red-400 bg-red-500/10' }} px-2 py-0.5 rounded">
                                {{ $device->has_internet ? 'ONLINE' : 'OFFLINE' }}
                            </span>
                        </div>
                        @endif

                        {{-- Tracking state --}}
                        @if($device->tracking_state)
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-slate-500 uppercase">Rastreo</span>
                            <span class="text-[9px] font-mono {{ str_contains($device->tracking_state, 'UNSAFE') ? 'text-amber-400 bg-amber-500/10' : 'text-emerald-400 bg-emerald-500/10' }} px-2 py-0.5 rounded">
                                {{ str_replace('_', ' ', $device->tracking_state) }}
                            </span>
                        </div>
                        @endif

                        {{-- Activity status --}}
                        @if($device->activity_status)
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-slate-500 uppercase">Estado</span>
                            <span class="text-[9px] font-mono text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded">
                                {{ $device->activity_status }}
                            </span>
                        </div>
                        @endif

                        {{-- Pantalla --}}
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-slate-500 uppercase">Pantalla</span>
                            <span class="text-[9px] font-mono {{ $device->screen_active ? 'text-white' : 'text-slate-500' }}">
                                {{ $device->screen_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>

                        {{-- Velocidad --}}
                        @if($device->speed_kmh !== null)
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-slate-500 uppercase">Velocidad</span>
                            <span class="text-[9px] font-mono text-emerald-400">
                                {{ number_format($device->speed_kmh, 1) }} km/h
                            </span>
                        </div>
                        @endif

                        {{-- Frecuencia de Envío --}}
                        @if($device->intervalo_aplicado)
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-slate-500 uppercase">Frecuencia</span>
                            <span class="text-[9px] font-mono text-cyan-400">
                                Cada {{ $device->intervalo_aplicado }}s
                            </span>
                        </div>
                        @endif

                        {{-- Rango/Motivo --}}
                        @if($device->motivo)
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-slate-500 uppercase">Categoría</span>
                            <span class="text-[9px] font-mono text-purple-400">
                                {{ strtoupper(str_replace('_', ' ', $device->motivo)) }}
                            </span>
                        </div>
                        @endif
                    </div>

                    <!-- Alerta de Zona Segura -->
                    <div class="p-4 rounded-xl border {{ $safePlaces->count() == 0 ? 'bg-slate-800/30 border-slate-700/50 text-slate-400' : ($isInsideSafeZone ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400 animate-pulse') }}">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="material-symbols-outlined text-lg">
                                {{ $safePlaces->count() == 0 ? 'info' : ($isInsideSafeZone ? 'verified_user' : 'warning') }}
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider">Perímetro Seguro</span>
                        </div>
                        <p class="text-xs font-semibold leading-snug">
                            @if($safePlaces->count() == 0)
                                Sin zonas seguras configuradas para este dispositivo.
                            @elseif($isInsideSafeZone)
                                Dispositivo Seguro. Dentro de: <span class="text-white underline">{{ $activeSafeZoneName }}</span>
                            @else
                                ⚠️ ALERTA: ¡El dispositivo se encuentra FUERA del rango de seguridad establecido!
                            @endif
                        </p>
                    </div>
                </div>

                <div class="bg-[#1c1e21] p-5 rounded-2xl border border-slate-800">
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Historial Diario</h4>
                    <form method="GET" action="{{ route('device.show', $device->id) }}">
                        <div class="flex gap-2">
                            <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()"
                                   class="bg-slate-900 border border-slate-800 rounded-xl py-3.5 px-4 text-xs text-white outline-none focus:border-[#00e5ff] w-full transition-all cursor-pointer font-bold">
                        </div>
                    </form>
                    <p class="text-[9px] text-slate-500 mt-2 font-mono">Mostrando registros para: {{ \Carbon\Carbon::parse($selectedDate)->format('d M, Y') }}</p>
                </div>

                <button class="mt-auto bg-red-500/10 border border-red-500/50 text-red-500 w-full py-4 rounded-xl font-bold text-xs uppercase hover:bg-red-500 hover:text-white transition-all flex items-center justify-center gap-2 shrink-0">
                    <span class="material-symbols-outlined text-sm">lock</span> Bloqueo de Emergencia
                </button>
            </div>

            <!-- Columna Central: Mapa Interactivo -->
            <div class="lg:col-span-6 h-full relative">
                <div class="absolute inset-0 bg-black rounded-3xl border border-slate-800 overflow-hidden shadow-2xl">
                    
                    <!-- Overlay de Fecha del Historial en el Mapa -->
                    <div class="absolute top-4 left-4 z-[400]">
                        <div class="bg-[#1c1e21]/90 backdrop-blur-md border border-slate-700 text-slate-300 rounded-xl px-4 py-2 text-xs font-bold tracking-wider flex items-center gap-2 shadow-lg">
                            <span class="material-symbols-outlined text-[#00e5ff] text-base">calendar_today</span>
                            Historial: {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                        </div>
                    </div>

                    <!-- Overlay flotante para agregar punto seguro -->
                    <div id="perimeter-helper" class="absolute top-4 right-4 z-[400] hidden">
                        <div class="bg-[#1c1e21]/95 backdrop-blur-md border border-[#6CD400] text-white rounded-xl p-4 shadow-xl max-w-xs animate-bounce">
                            <div class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-[#6CD400] text-lg mt-0.5">place</span>
                                <div>
                                    <h5 class="text-xs font-bold mb-1">Añadir Punto Seguro</h5>
                                    <p class="text-[10px] text-slate-400 leading-normal">Haz clic en el mapa para ubicar el centro de tu nueva zona segura.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario Flotante de Punto Seguro (Se activa tras clic) -->
                    <div id="safe-place-form-card" class="absolute left-4 bottom-4 z-[400] hidden max-w-sm w-full mx-4">
                        <div class="bg-[#1c1e21]/95 backdrop-blur-md border border-slate-800 rounded-2xl p-5 shadow-2xl text-slate-100">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-xs font-black uppercase text-white flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[#6CD400] text-base">verified_user</span>
                                    Guardar Zona Segura
                                </h4>
                                <button onclick="cancelSafePlace()" class="text-slate-500 hover:text-white transition-colors">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                </button>
                            </div>
                            
                            <form action="{{ route('safe-place.store', $device->id) }}" method="POST" class="space-y-4">
                                @csrf
                                <input type="hidden" name="latitude" id="form-lat">
                                <input type="hidden" name="longitude" id="form-lng">
                                
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1 tracking-wider">Nombre del Lugar</label>
                                    <input type="text" name="name" required placeholder="Ej. Casa de Abuelos, Escuela"
                                           class="w-full bg-slate-900 border border-slate-800 rounded-xl py-2 px-3 text-xs text-white placeholder-slate-600 outline-none focus:border-[#6CD400] transition-all">
                                </div>

                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider">Diámetro / Radio (Meters)</label>
                                        <span id="radius-value" class="text-xs font-mono font-bold text-[#6CD400]">150m</span>
                                    </div>
                                    <input type="range" name="radius_meters" id="radius-slider" min="50" max="1000" step="25" value="150"
                                           class="w-full accent-[#6CD400] bg-slate-900 border border-slate-800 rounded-lg"
                                           oninput="updateCircleRadius(this.value)">
                                </div>

                                <button type="submit" 
                                        class="w-full bg-[#6CD400] hover:bg-[#5bb300] text-white py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
                                    Guardar Perímetro
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="map"></div>
                </div>
            </div>

            <!-- Columna Derecha: Zonas Seguras, Coordenadas e Historial de Pings -->
            <div class="lg:col-span-3 flex flex-col gap-4 h-full overflow-y-auto pl-2">
                <div class="bg-[#1c1e21] p-5 rounded-2xl border border-slate-800 shrink-0">
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Métricas del Punto Actual</h4>
                    <div class="bg-black/40 p-4 rounded-xl border border-white/5 relative group">
                        <button onclick="copyToClipboard('{{ $device->latitude }}, {{ $device->longitude }}')" class="absolute right-3 top-3 text-slate-600 hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-sm">content_copy</span>
                        </button>
                        <p class="text-[10px] text-slate-500 mb-1">Coordenadas del Sensor</p>
                        <div class="font-mono text-xs text-white">
                            <p>LAT: {{ number_format($device->latitude, 6) }}°</p>
                            <p>LNG: {{ number_format($device->longitude, 6) }}°</p>
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-white/5 pt-2">
                            <span class="text-[10px] text-slate-500">Última Señal</span>
                            <span class="text-[9px] text-[#00e5ff] font-bold flex items-center gap-1">
                                <span class="size-1.5 bg-[#00e5ff] rounded-full animate-ping"></span>
                                {{ $device->last_seen ? $device->last_seen->diffForHumans() : 'SIN DATOS' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Lista de Zonas Seguras -->
                <div class="bg-[#1c1e21] p-5 rounded-2xl border border-slate-800 shrink-0">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Zonas Seguras ({{ $safePlaces->count() }})</h4>
                    </div>
                    <div class="space-y-3 max-h-[180px] overflow-y-auto pr-1">
                        @forelse($safePlaces as $place)
                        <div class="flex items-center justify-between p-3 bg-black/20 rounded-xl border border-white/5">
                            <div>
                                <p class="text-xs font-bold text-white">{{ $place->name }}</p>
                                <p class="text-[10px] text-[#6CD400] font-mono">Radio: {{ $place->radius_meters }}m</p>
                            </div>
                            <form action="{{ route('safe-place.destroy', $place->id) }}" method="POST" 
                                  onsubmit="return confirm('¿Eliminar la zona segura {{ $place->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500/70 hover:text-red-500 transition-colors p-1">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </form>
                        </div>
                        @empty
                        <div class="text-center py-4 text-slate-600 text-xs italic">
                            No hay perímetros creados.
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Historial de Pings del Día -->
                <div class="bg-[#1c1e21] p-5 rounded-2xl border border-slate-800 flex-1 min-h-[200px]">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest font-mono">Movimientos de Hoy</h4>
                        <span class="text-[9px] text-[#00e5ff] font-bold">HISTORIAL</span>
                    </div>
                    <div class="space-y-3 max-h-[220px] overflow-y-auto pr-1">
                        @forelse($locationHistories->sortByDesc('created_at')->take(10) as $history)
                        <div class="p-3 rounded-xl border border-white/5 bg-transparent opacity-80 hover:opacity-100 transition-opacity">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-white font-bold text-[11px]">{{ $history->created_at->format('H:i') }} ({{ $history->created_at->diffForHumans(null, true, true) }} ago)</span>
                                <span class="text-[10px] font-bold font-mono {{ ($history->activity == 'moving' || in_array($history->movement_type, ['WALKING', 'RUNNING', 'VEHICLE'])) ? 'text-[#6CD400]' : 'text-slate-500' }}">
                                    {{ strtoupper($history->movement_type ?? $history->activity) }}
                                </span>
                            </div>
                            <p class="text-[9px] text-slate-400 font-mono">
                                Batería: {{ $history->battery_level }}% • Red: {{ strtoupper($history->connection_type ?? 'N/A') }}
                            </p>
                            @if($history->speed_kmh !== null || $history->intervalo_aplicado)
                            <p class="text-[9px] text-slate-500 font-mono mt-0.5">
                                @if($history->speed_kmh !== null) Velocidad: <span class="text-emerald-400">{{ number_format($history->speed_kmh, 1) }} km/h</span> @endif
                                @if($history->intervalo_aplicado) • Frecuencia: <span class="text-cyan-400">{{ $history->intervalo_aplicado }}s</span> @endif
                                @if($history->motivo) • Motivo: <span class="text-purple-400">{{ strtoupper(str_replace('_', ' ', $history->motivo)) }}</span> @endif
                            </p>
                            @endif
                        </div>
                        @empty
                        <div class="text-center py-8 text-slate-600 text-xs italic">
                            Sin transmisiones reportadas para este día.
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Herramientas de Zona Segura -->
                <div class="bg-[#1c1e21] p-5 rounded-2xl border border-slate-800 space-y-3 shrink-0">
                    <button id="btn-draw" onclick="toggleDrawingMode()" 
                            class="w-full flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 hover:bg-slate-700 transition-all border border-slate-700 text-left group">
                        <div class="bg-[#6CD400]/20 group-hover:bg-[#6CD400]/40 p-1.5 rounded-lg text-[#6CD400] transition-colors">
                            <span class="material-symbols-outlined text-sm">edit_square</span>
                        </div>
                        <span class="text-xs font-bold text-slate-300">Crear Zona Segura</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // 1. Definir coordenadas base y datos de historial
        const lat = {{ $device->latitude ?? 19.4326 }};
        const lng = {{ $device->longitude ?? -99.1332 }};

        // 2. Inicializar Mapa
        var map = L.map('map', {
            zoomControl: false,
            attributionControl: false
        }).setView([lat, lng], 15);

        // 3. Capa de Mapa (Midnight Blue)
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 16
        }).addTo(map);

        // 4. Cargar Historial Telemetría Completa desde Laravel
        var telemetryHistory = [
            @foreach ($locationHistories as $point)
            {
                lat: {{ $point->latitude }},
                lng: {{ $point->longitude }},
                battery: {{ $point->battery_level ?? 100 }},
                is_charging: {{ $point->is_charging ? 'true' : 'false' }},
                activity: "{{ $point->activity ?? 'still' }}",
                movement_type: "{{ $point->movement_type ?? 'STATIC' }}",
                screen_active: {{ $point->screen_active ? 'true' : 'false' }},
                time: "{{ $point->created_at->toIso8601String() }}",
                label_time: "{{ $point->created_at->format('H:i:s') }}"
            },
            @endforeach
        ];

        // 5. Preparar arquitectura de Estilos Dinámicos
        var routeStyles = {
            'WALKING': { color: '#00e5ff', weight: 5, opacity: 0.8, dashArray: '1, 10', dashOffset: '10', lineJoin: 'round' },
            'RUNNING': { color: '#ff0055', weight: 6, opacity: 0.9, dashArray: '4, 8', dashOffset: '0', lineJoin: 'round' },
            'VEHICLE': { color: '#6CD400', weight: 6, opacity: 0.9, dashArray: null, smoothFactor: 2.0, lineJoin: 'round' },
            'STATIC':  { color: '#888888', weight: 4, opacity: 0.5, dashArray: '2, 4', lineJoin: 'round' },
            'DEFAULT': { color: '#00e5ff', weight: 5, opacity: 0.8, dashArray: '1, 10', dashOffset: '10', lineJoin: 'round' } // fallback
        };

        // 6. Filtrado y Suavizado de Trayectorias
        var sortedTelemetry = [...telemetryHistory].sort(function(a, b) {
            return new Date(a.time) - new Date(b.time);
        });

        // Función para detectar saltos rectos exagerados (filtro de anomalías GPS / altas velocidades imposibles)
        function isAnomalousJump(p1, p2) {
            var tDiff = (new Date(p2.time) - new Date(p1.time)) / 3600000; // horas
            if (tDiff <= 0) return false;
            
            var R = 6371; // km
            var dLat = (p2.lat - p1.lat) * Math.PI / 180;
            var dLon = (p2.lng - p1.lng) * Math.PI / 180;
            var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(p1.lat * Math.PI / 180) * Math.cos(p2.lat * Math.PI / 180) *
                    Math.sin(dLon/2) * Math.sin(dLon/2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            var dist = R * c;
            
            var speed = dist / tDiff; // km/h
            return speed > 200; // Más de 200 km/h se considera anómalo para estos sensores
        }

        var cleanTelemetry = [];
        for (var i = 0; i < sortedTelemetry.length; i++) {
            if (i > 0 && isAnomalousJump(cleanTelemetry[cleanTelemetry.length - 1], sortedTelemetry[i])) {
                continue; // Omitir salto
            }
            cleanTelemetry.push(sortedTelemetry[i]);
        }

        // Dividir la ruta en segmentos según el tipo de movimiento
        var segments = [];
        var currentSegment = [];
        var currentType = null;

        cleanTelemetry.forEach(function(point) {
            var type = point.movement_type || 'DEFAULT';
            
            if (currentType !== type) {
                if (currentSegment.length > 0) {
                    segments.push({ type: currentType, points: currentSegment });
                }
                // Conectar segmentos visualmente repitiendo el último punto
                currentSegment = currentSegment.length > 0 ? [currentSegment[currentSegment.length - 1], point] : [point];
                currentType = type;
            } else {
                currentSegment.push(point);
            }
        });
        if (currentSegment.length > 0) {
            segments.push({ type: currentType, points: currentSegment });
        }

        // Renderizado de las rutas diferenciando los trayectos
        var polylineBounds = L.latLngBounds();
        segments.forEach(function(segment) {
            var style = routeStyles[segment.type] || routeStyles['DEFAULT'];
            var rawPoints = segment.points.map(function(p) { return [p.lat, p.lng]; });
            var smoothedPoints = rawPoints;
            
            // Suavizado de coordenadas (interpolación visual) para vehículos
            if (segment.type === 'VEHICLE' && rawPoints.length >= 3) {
                smoothedPoints = [rawPoints[0]];
                for (var i = 1; i < rawPoints.length - 1; i++) {
                    var prev = rawPoints[i-1];
                    var curr = rawPoints[i];
                    var next = rawPoints[i+1];
                    // Reducción de líneas agresivas: promedio móvil
                    var lat = (prev[0] + curr[0]*2 + next[0]) / 4;
                    var lng = (prev[1] + curr[1]*2 + next[1]) / 4;
                    smoothedPoints.push([lat, lng]);
                }
                smoothedPoints.push(rawPoints[rawPoints.length - 1]);
            }
            
            if (smoothedPoints.length > 1) {
                var pl = L.polyline(smoothedPoints, style).addTo(map);
                polylineBounds.extend(pl.getBounds());
            } else if (smoothedPoints.length === 1) {
                polylineBounds.extend(smoothedPoints[0]);
            }
        });

        if (cleanTelemetry.length > 0 && polylineBounds.isValid()) {
            map.fitBounds(polylineBounds, { padding: [50, 50] });
        }

        // 7. Algoritmo para encontrar y reportar Paradas Estáticas (Still Stops)
        // Agrupamos puntos consecutivos con actividad 'still'
        var staticStops = [];
        var currentStopGroup = [];

        telemetryHistory.forEach(function(point) {
            var act = (point.activity || '').toLowerCase();
            if (act === 'still') {
                currentStopGroup.push(point);
            } else {
                if (currentStopGroup.length > 0) {
                    processStopGroup(currentStopGroup);
                    currentStopGroup = [];
                }
            }
        });
        if (currentStopGroup.length > 0) {
            processStopGroup(currentStopGroup);
        }

        function processStopGroup(group) {
            var first = group[0];
            var last = group[group.length - 1];
            
            // Calcular tiempo de reposo
            var start = new Date(first.time);
            var end = new Date(last.time);
            var durationMs = end - start;
            var durationMins = Math.round(durationMs / 60000);
            
            // Si el tiempo es menor a 1 minuto, le damos 1 min por defecto de reporte
            if (durationMins < 1) durationMins = 2; 

            // Calcular uso de pantalla en minutos sumando los tramos donde estuvo encendida
            var screenActiveMs = 0;
            for (var i = 0; i < group.length - 1; i++) {
                if (group[i].screen_active) {
                    screenActiveMs += (new Date(group[i+1].time) - new Date(group[i].time));
                }
            }
            var screenMins = Math.round(screenActiveMs / 60000);
            
            staticStops.push({
                lat: first.lat,
                lng: first.lng,
                restingTime: durationMins,
                screenMinutes: screenMins,
                battery: last.battery,
                timeLabel: first.label_time + ' - ' + last.label_time
            });
        }

        // 8. Colocar Marcadores Amarillos con Flechas para las Paradas Estáticas
        staticStops.forEach(function(stop) {
            var stopIcon = L.divIcon({
                className: 'custom-stop-icon',
                html: `
                    <div style="position: relative; display: flex; align-items: center; justify-content: center;">
                        <div style="background-color: #ffd600; width: 14px; height: 14px; border: 2.5px solid #131416; border-radius: 50%; box-shadow: 0 0 12px #ffd600; z-index: 2; position: absolute;"></div>
                        <div class="pulse-yellow" style="background-color: #ffd600; width: 14px; height: 14px; border-radius: 50%; position: absolute;"></div>
                        <span class="material-symbols-outlined text-[#ffd600]" style="font-size: 20px; font-weight: bold; position: absolute; top: -20px; text-shadow: 0 0 8px #ffd600;">arrow_downward</span>
                    </div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            L.marker([stop.lat, stop.lng], { icon: stopIcon })
                .addTo(map)
                .bindPopup(`
                    <div class="text-slate-900 font-sans p-1">
                        <b class="text-xs uppercase text-amber-500 font-bold block mb-1">📍 Parada Estática</b>
                        <div class="text-[10px] space-y-1 font-semibold text-slate-700">
                            <p>⏳ <b>Tiempo reposo:</b> ${stop.restingTime} min</p>
                            <p>📱 <b>Uso de pantalla:</b> ${stop.screenMinutes} min</p>
                            <p>🔋 <b>Batería:</b> ${stop.battery}%</p>
                            <p class="text-[8px] text-slate-400 font-mono pt-1">Hora: ${stop.timeLabel}</p>
                        </div>
                    </div>
                `);
        });

        // 9. Dibujar las Zonas Seguras de la Base de Datos
        @foreach($safePlaces as $place)
            var safeCircle = L.circle([{{ $place->latitude }}, {{ $place->longitude }}], {
                color: '#6CD400',
                fillColor: '#6CD400',
                fillOpacity: 0.15,
                weight: 2,
                dashArray: '4, 6',
                radius: {{ $place->radius_meters }}
            }).addTo(map);
            
            safeCircle.bindPopup(`
                <div class="text-slate-900 font-sans p-1">
                    <b class="text-xs text-emerald-600 block">🛡️ Perímetro Seguro</b>
                    <p class="text-[10px] text-slate-700 font-bold">Lugar: {{ $place->name }}</p>
                    <p class="text-[9px] text-slate-400 font-mono">Radio: {{ $place->radius_meters }}m</p>
                </div>
            `);
        @endforeach

        // 10. Icono de ubicación actual pulsante verde
        var unitIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `
                <div style="position: relative;">
                    <div style="background-color: #6CD400; width: 24px; height: 24px; border: 4px solid #ffffff; border-radius: 50%; box-shadow: 0 0 20px rgba(108, 212, 0, 0.8); z-index: 2; position: absolute;"></div>
                    <div style="background-color: #6CD400; width: 24px; height: 24px; border-radius: 50%; animation: pulse 2s infinite; opacity: 0.5; position: absolute;"></div>
                </div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        // Colocar Marcador Actual
        if (lat && lng) {
            L.marker([lat, lng], { icon: unitIcon })
                .addTo(map)
                .bindPopup('<b class="text-slate-900">{{ $device->alias }} (Actual)</b>');
        }

        // --- SISTEMA INTERACTIVO DE CREACIÓN DE ZONA SEGURA ---
        var isDrawingMode = false;
        var creationMarker = null;
        var creationCircle = null;

        function toggleDrawingMode() {
            isDrawingMode = !isDrawingMode;
            var btn = document.getElementById('btn-draw');
            var helper = document.getElementById('perimeter-helper');
            
            if (isDrawingMode) {
                btn.classList.add('bg-[#6CD400]/20', 'border-[#6CD400]');
                btn.querySelector('span').innerText = 'Crear Zona Segura (Activo)';
                helper.classList.remove('hidden');
                map.getContainer().style.cursor = 'crosshair';
            } else {
                resetDrawingState();
            }
        }

        function resetDrawingState() {
            isDrawingMode = false;
            var btn = document.getElementById('btn-draw');
            var helper = document.getElementById('perimeter-helper');
            var formCard = document.getElementById('safe-place-form-card');
            
            btn.classList.remove('bg-[#6CD400]/20', 'border-[#6CD400]');
            btn.querySelector('span').innerText = 'Crear Zona Segura';
            helper.classList.add('hidden');
            formCard.classList.add('hidden');
            map.getContainer().style.cursor = '';
            
            if (creationMarker) map.removeLayer(creationMarker);
            if (creationCircle) map.removeLayer(creationCircle);
            creationMarker = null;
            creationCircle = null;
        }

        // Evento clic en mapa para definir las coordenadas de zona segura
        map.on('click', function(e) {
            if (!isDrawingMode) return;

            var clickedLat = e.latlng.lat;
            var clickedLng = e.latlng.lng;

            // Rellenar campos del formulario flotante
            document.getElementById('form-lat').value = clickedLat;
            document.getElementById('form-lng').value = clickedLng;
            
            // Mostrar formulario flotante
            document.getElementById('safe-place-form-card').classList.remove('hidden');

            // Actualizar o colocar marcador y círculo temporal en el mapa
            var radius = parseInt(document.getElementById('radius-slider').value);

            if (creationMarker) {
                creationMarker.setLatLng(e.latlng);
                creationCircle.setLatLng(e.latlng);
                creationCircle.setRadius(radius);
            } else {
                creationMarker = L.marker(e.latlng, { draggable: true }).addTo(map);
                creationCircle = L.circle(e.latlng, {
                    color: '#6CD400',
                    fillColor: '#6CD400',
                    fillOpacity: 0.25,
                    radius: radius
                }).addTo(map);

                // Si arrastran el marcador, actualizamos las coordenadas
                creationMarker.on('drag', function(evt) {
                    var newPos = evt.target.getLatLng();
                    document.getElementById('form-lat').value = newPos.lat;
                    document.getElementById('form-lng').value = newPos.lng;
                    creationCircle.setLatLng(newPos);
                });
            }
        });

        // Actualizar diámetro/radio dinámicamente cuando el usuario desliza el input range
        function updateCircleRadius(val) {
            document.getElementById('radius-value').innerText = val + 'm';
            if (creationCircle) {
                creationCircle.setRadius(parseInt(val));
            }
        }

        function cancelSafePlace() {
            resetDrawingState();
        }

        // Función auxiliar para copiar coordenadas
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert('Coordenadas copiadas al portapapeles.');
        }

        // Corregir cálculo de tamaño de Leaflet tras cargar el DOM y CSS
        window.addEventListener('load', function() {
            setTimeout(function() {
                map.invalidateSize();
                if (cleanTelemetry.length > 0 && polylineBounds.isValid()) {
                    map.fitBounds(polylineBounds, { padding: [50, 50] });
                }
            }, 100);
        });
    </script>
</body>
</html>