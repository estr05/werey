<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warey | Unit Control Center</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              primary: "#06b6d4",
              "background-light": "#f8fafc",
              "background-dark": "#0a0c10",
              "surface-dark": "#161b22",
              "border-dark": "#30363d",
            },
            fontFamily: {
              sans: ["Inter", "sans-serif"],
              mono: ["JetBrains Mono", "monospace"],
            },
            borderRadius: {
              DEFAULT: "0.5rem",
            },
          },
        },
      };
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .map-grid {
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0);
            background-size: 40px 40px;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #484f58; }

        #map { height: 100%; width: 100%; border-radius: 16px; z-index: 0; }
        .leaflet-container { background: transparent !important; }

        @keyframes pulse-yellow-key {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(2.2); opacity: 0; }
        }
        .pulse-yellow {
            animation: pulse-yellow-key 2s infinite;
        }
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
                    </div>
                    <span class="w-2 h-2 rounded-full {{ $device->last_seen && $device->last_seen->gt(now()->subMinutes(5)) ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600' }}"></span>
                </div>
                <div class="mb-2">
                    <div class="flex items-end gap-1 mb-1">
                        <span class="text-4xl font-bold mono">{{ $device->battery_level ?? 0 }}</span>
                        <span class="text-lg text-slate-500 mb-1 font-bold">%</span>
                    </div>
                    <p class="text-[10px] text-slate-500 font-semibold mb-3">Cargando: {{ $device->is_charging ? 'SÍ' : 'NO' }}</p>
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full {{ $device->battery_level > 20 ? 'bg-primary' : 'bg-red-500' }} rounded-full" style="width: {{ $device->battery_level ?? 0 }}%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark flex-1 shrink-0">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Estado de Telemetría</h3>
                </div>
                <div class="p-4 space-y-4">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Tipo de Conexión</span>
                        <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-500 font-bold uppercase text-[9px]">{{ strtoupper($device->connection_type ?? 'N/A') }}</span>
                    </div>

                    @if($device->has_internet !== null)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Internet</span>
                        <span class="px-2 py-0.5 rounded {{ $device->has_internet ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500' }} font-bold uppercase text-[9px]">
                            {{ $device->has_internet ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    @endif

                    @if($device->tracking_state)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Rastreo</span>
                        <span class="px-2 py-0.5 rounded {{ str_contains($device->tracking_state, 'UNSAFE') ? 'bg-amber-500/10 text-amber-500' : 'bg-emerald-500/10 text-emerald-500' }} font-bold uppercase text-[9px]">
                            {{ str_replace('_', ' ', $device->tracking_state) }}
                        </span>
                    </div>
                    @endif

                    @if($device->activity_status)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Estado</span>
                        <span class="px-2 py-0.5 rounded bg-slate-500/10 text-slate-500 font-bold uppercase text-[9px]">{{ $device->activity_status }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Pantalla</span>
                        <span class="{{ $device->screen_active ? 'text-primary' : 'text-slate-500' }} font-bold uppercase text-[10px]">
                            {{ $device->screen_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Velocidad</span>
                        <span id="live-speed" class="mono font-bold text-emerald-500">
                            {{ $device->speed_kmh !== null ? number_format($device->speed_kmh, 1).' km/h' : '--' }}
                        </span>
                    </div>

                    @if($device->intervalo_aplicado)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Frecuencia</span>
                        <span class="text-primary font-bold uppercase text-[10px]">Cada {{ $device->intervalo_aplicado }}s</span>
                    </div>
                    @endif

                    @if($device->motivo)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Categoría</span>
                        <span class="px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-500 font-bold uppercase text-[9px]">
                            {{ strtoupper(str_replace('_', ' ', $device->motivo)) }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="rounded-xl p-4 shrink-0 {{ $safePlaces->count() == 0 ? 'bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700' : ($isInsideSafeZone ? 'bg-emerald-500/5 border border-emerald-500/20' : 'bg-amber-500/5 border border-amber-500/20 animate-pulse') }}">
                <div class="flex items-center gap-2 mb-2 {{ $safePlaces->count() == 0 ? 'text-slate-500' : ($isInsideSafeZone ? 'text-emerald-500' : 'text-amber-500') }}">
                    <span class="material-icons-round text-sm">{{ $safePlaces->count() == 0 ? 'info' : ($isInsideSafeZone ? 'verified_user' : 'warning') }}</span>
                    <span class="text-[10px] font-bold uppercase tracking-widest">Perímetro Seguro</span>
                </div>
                <p class="text-xs leading-relaxed">
                    @if($safePlaces->count() == 0)
                        Sin zonas seguras configuradas.
                    @elseif($isInsideSafeZone)
                        Dispositivo Seguro. Dentro de: <span class="font-bold border-b {{ $isInsideSafeZone ? 'border-emerald-500/30' : 'border-amber-500/30' }}">{{ $activeSafeZoneName }}</span>
                    @else
                        ⚠️ ALERTA: Fuera de perímetro seguro.
                    @endif
                </p>
            </div>

            <button class="w-full py-3 bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/20 rounded-xl transition-all flex items-center justify-center gap-2 shrink-0">
                <span class="material-icons-round text-sm">lock_person</span>
                <span class="text-[10px] font-bold uppercase tracking-widest">Bloqueo de Emergencia</span>
            </button>
        </aside>

        <section class="flex-1 relative rounded-2xl overflow-hidden border border-slate-200 dark:border-border-dark bg-slate-100 dark:bg-[#0d1117] map-grid shadow-lg">

            <div id="map-loader" class="absolute inset-0 bg-white/80 dark:bg-[#0d1117]/80 backdrop-blur-sm z-[500] hidden flex flex-col items-center justify-center">
                <div class="w-10 h-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                <span class="text-xs font-mono font-bold text-primary tracking-widest animate-pulse uppercase">Cargando Historial...</span>
            </div>

            <div class="absolute top-4 left-4 z-[400] flex gap-3">
                <div class="bg-white/80 dark:bg-surface-dark/90 backdrop-blur-md px-3 py-2 rounded-lg border border-slate-200 dark:border-border-dark shadow-xl flex items-center gap-3">
                    <span class="material-icons-round text-primary text-sm">calendar_today</span>
                    <div class="flex flex-col relative">
                        <span class="text-[9px] uppercase font-bold text-slate-500 leading-none mb-0.5">Historial</span>
                        <select onchange="window.location.href = '?date=' + this.value;" class="bg-transparent border-none outline-none cursor-pointer font-mono font-bold appearance-none p-0 m-0 text-xs text-slate-900 dark:text-white leading-none">
                            @if(!in_array($selectedDate, $availableDates->toArray()))
                                <option value="{{ $selectedDate }}" class="bg-white dark:bg-surface-dark" selected>{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} (Sin datos)</option>
                            @endif
                            @foreach($availableDates as $date)
                                <option value="{{ $date }}" class="bg-white dark:bg-surface-dark" {{ $date == $selectedDate ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <span class="material-icons-round text-slate-400 text-sm pointer-events-none">expand_more</span>
                </div>

                <div id="live-indicator" class="bg-white/80 dark:bg-surface-dark/90 backdrop-blur-md px-3 py-2 rounded-lg border border-slate-200 dark:border-border-dark shadow-xl flex items-center gap-2">
                    <span id="live-dot" class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span id="live-text" class="text-[10px] font-bold uppercase text-amber-500 tracking-wider">Conectando...</span>
                </div>
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

            <div id="safe-place-form-card" class="absolute left-4 bottom-4 z-[400] hidden max-w-sm w-full mx-4">
                <div class="bg-white/95 dark:bg-surface-dark/95 backdrop-blur-md border border-slate-200 dark:border-border-dark rounded-2xl p-5 shadow-2xl text-slate-900 dark:text-slate-100">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-xs font-black uppercase flex items-center gap-2">
                            <span class="material-icons-round text-primary text-base">verified_user</span>
                            Guardar Zona Segura
                        </h4>
                        <button onclick="cancelSafePlace()" class="text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
                            <span class="material-icons-round text-sm">close</span>
                        </button>
                    </div>

                    <form action="{{ route('safe-place.store', $device) }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="latitude" id="form-lat">
                        <input type="hidden" name="longitude" id="form-lng">

                        <div>
                            <label class="block text-[9px] font-bold text-slate-500 uppercase mb-1 tracking-wider">Nombre del Lugar</label>
                            <input type="text" name="name" required placeholder="Ej. Casa de Abuelos, Escuela"
                                   class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-border-dark rounded-xl py-2 px-3 text-xs text-slate-900 dark:text-white placeholder-slate-400 outline-none focus:border-primary transition-all">
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider">Diámetro / Radio (Meters)</label>
                                <span id="radius-value" class="text-xs font-mono font-bold text-primary">150m</span>
                            </div>
                            <input type="range" name="radius_meters" id="radius-slider" min="50" max="1000" step="25" value="150"
                                   class="w-full accent-primary bg-slate-200 dark:bg-background-dark border border-slate-200 dark:border-border-dark rounded-lg"
                                   oninput="updateCircleRadius(this.value)">
                        </div>

                        <button type="submit"
                                class="w-full bg-primary hover:bg-cyan-600 text-white py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
                            Guardar Perímetro
                        </button>
                    </form>
                </div>
            </div>

            <div id="map"></div>
        </section>

        <aside class="w-96 flex flex-col gap-4 overflow-y-auto pl-2 shrink-0">
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark flex flex-col overflow-hidden shrink-0">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Métricas del Punto Actual</h3>
                    <span class="material-icons-round text-sm text-slate-400">info</span>
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
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] uppercase font-medium text-slate-500">Última Señal</span>
                        <span id="live-last-seen" class="text-[10px] font-bold text-primary uppercase">
                            {{ $device->last_seen ? $device->last_seen->diffForHumans() : 'SIN DATOS' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark shrink-0">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Zonas Seguras ({{ $safePlaces->count() }})</h3>
                    <button id="btn-draw" onclick="toggleDrawingMode()" class="text-[10px] font-bold uppercase text-primary border-b border-primary/30 flex items-center gap-1 group">
                        <span class="material-icons-round text-[10px]">add</span> Crear
                    </button>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-border-dark max-h-[200px] overflow-y-auto">
                    @forelse($safePlaces as $place)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                        <div>
                            <p class="text-sm font-bold">{{ $place->name }}</p>
                            <p class="text-[10px] text-emerald-500 font-bold uppercase">Radio: {{ $place->radius_meters }}m</p>
                        </div>
                        <form action="{{ route('safe-place.destroy', $place->id) }}" method="POST" onsubmit="return confirm('¿Eliminar la zona segura {{ $place->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                <span class="material-icons-round text-sm">delete_outline</span>
                            </button>
                        </form>
                    </div>
                    @empty
                    <div class="p-4 text-center text-xs text-slate-500 italic">No hay perímetros creados.</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark flex-1 flex flex-col min-h-0 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Movimientos de Hoy</h3>
                    <span class="text-[10px] font-bold uppercase text-primary">Historial</span>
                </div>
                <div id="history-container" class="flex-1 overflow-y-auto p-4 space-y-6 relative">
                </div>
            </div>
        </aside>
    </main>

    <footer class="h-8 px-6 bg-slate-50 dark:bg-[#0d1117] border-t border-slate-200 dark:border-border-dark flex items-center justify-between shrink-0">
        <div class="flex gap-4">
            <div class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span class="text-[9px] font-bold uppercase text-slate-500">System Ready</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                <span class="text-[9px] font-bold uppercase text-slate-500">Node {{ $device->alias }} Connected</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-[9px] font-bold uppercase text-slate-400">Precision: ± 2.4m</span>
            <span class="text-[9px] font-mono text-slate-400">V.2.4.0-STABLE</span>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
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
    <script src="{{ asset('js/device-detail.js') }}"></script>
</body>
</html>