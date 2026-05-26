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

        /* Map must always have an explicit height or Leaflet renders nothing */
        #map { height: 100%; min-height: 60vh; width: 100%; border-radius: 16px; z-index: 0; }
        @media (min-width: 1024px) {
            #map { min-height: unset; height: 100%; }
        }
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

<body class="bg-background-dark text-slate-100 h-screen h-[100dvh] overflow-hidden flex flex-col">

    <!-- Backdrop drawers mÃ³vil -->
    <div id="drawers-backdrop" onclick="closeAllDrawers()" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden opacity-0 transition-opacity duration-300 lg:hidden"></div>

    <!-- HEADER -->
    <header class="h-14 lg:h-16 flex items-center justify-between px-4 lg:px-6 border-b border-border-dark bg-surface-dark z-10 shrink-0">
        <div class="flex items-center gap-3">
            <button onclick="openLeftDrawer()" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-slate-700 transition-colors border border-slate-700">
                <span class="material-icons-round text-slate-300">menu</span>
            </button>
            <a href="{{ route('dashboard') }}" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-slate-700 transition-colors">
                <span class="material-icons-round text-slate-300">arrow_back</span>
            </a>
            <div>
                <h1 class="text-base lg:text-xl font-bold tracking-tight uppercase leading-none">Unit Control Center</h1>
                <p class="text-[10px] uppercase tracking-widest text-slate-500 font-medium hidden sm:block">Rastreo de telemetrÃ­a en tiempo real</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-primary/30 bg-primary/5 text-[10px] font-semibold text-primary uppercase tracking-wider">
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                <span class="hidden sm:inline">Encrypted Link Active</span>
                <span class="sm:hidden">Live</span>
            </div>
            <button onclick="openRightDrawer()" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-slate-700 transition-colors border border-slate-700">
                <span class="material-icons-round text-slate-300">tune</span>
            </button>
        </div>
    </header>


    <!-- MAIN -->
    <main class="flex-1 flex flex-col lg:flex-row overflow-y-auto lg:overflow-hidden p-2 lg:p-4 gap-4 min-h-0">

        <!-- LEFT PANEL: TelemetrÃ­a -->
        <aside id="left-drawer" class="fixed inset-y-0 left-0 w-[85vw] max-w-[320px] bg-surface-dark border-r border-border-dark z-50 transform -translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto lg:static lg:w-80 lg:max-w-none lg:translate-x-0 lg:border-none lg:bg-transparent lg:z-auto lg:shrink-0 flex flex-col gap-4 p-4 lg:p-0 lg:pr-0">

            <!-- Mobile close header -->
            <div class="flex lg:hidden justify-between items-center py-3 border-b border-border-dark mb-2">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider">TelemetrÃ­a</h2>
                <button onclick="closeAllDrawers()" class="text-slate-500 hover:text-white">
                    <span class="material-icons-round">close</span>
                </button>
            </div>

            <!-- Activity + Battery Card -->
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark p-5 shrink-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-500">
                            <span class="material-icons-round text-xl">sensors</span>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase text-slate-500 font-bold">Actividad</p>
                            <p class="text-sm font-bold uppercase">{{ $device->activity ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <span class="w-2 h-2 rounded-full {{ $device->last_seen && $device->last_seen->gt(now()->subMinutes(5)) ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600' }}"></span>
                </div>
                <div class="mb-2">
                    <div class="flex items-end gap-1 mb-1">
                        <span class="text-4xl font-bold mono">{{ $device->battery_level ?? 0 }}</span>
                        <span class="text-lg text-slate-500 mb-1 font-bold">%</span>
                    </div>
                    <p class="text-[10px] text-slate-500 font-semibold mb-3">Cargando: {{ $device->is_charging ? 'SÃ' : 'NO' }}</p>
                    <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full {{ ($device->battery_level ?? 0) > 20 ? 'bg-primary' : 'bg-red-500' }} rounded-full" style="width: {{ $device->battery_level ?? 0 }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Telemetry Status -->
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark shrink-0">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Estado de TelemetrÃ­a</h3>
                </div>
                <div class="p-4 space-y-4">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Tipo de ConexiÃ³n</span>
                        <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-500 font-bold uppercase text-[9px]">{{ strtoupper($device->connection_type ?? 'N/A') }}</span>
                    </div>
                    @if($device->has_internet !== null)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Internet</span>
                        <span class="px-2 py-0.5 rounded {{ $device->has_internet ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500' }} font-bold uppercase text-[9px]">{{ $device->has_internet ? 'Online' : 'Offline' }}</span>
                    </div>
                    @endif
                    @if($device->tracking_state)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Rastreo</span>
                        <span class="px-2 py-0.5 rounded {{ str_contains($device->tracking_state, 'UNSAFE') ? 'bg-amber-500/10 text-amber-500' : 'bg-emerald-500/10 text-emerald-500' }} font-bold uppercase text-[9px]">{{ str_replace('_', ' ', $device->tracking_state) }}</span>
                    </div>
                    @endif
                    @if($device->activity_status)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Estado</span>
                        <span class="px-2 py-0.5 rounded bg-slate-500/10 text-slate-400 font-bold uppercase text-[9px]">{{ $device->activity_status }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Pantalla</span>
                        <span class="{{ $device->screen_active ? 'text-primary' : 'text-slate-500' }} font-bold uppercase text-[10px]">{{ $device->screen_active ? 'Activa' : 'Inactiva' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Velocidad</span>
                        <span id="live-speed" class="mono font-bold text-emerald-500">{{ $device->speed_kmh !== null ? number_format($device->speed_kmh, 1).' km/h' : '--' }}</span>
                    </div>
                    @if($device->intervalo_aplicado)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">Frecuencia</span>
                        <span class="text-primary font-bold uppercase text-[10px]">Cada {{ $device->intervalo_aplicado }}s</span>
                    </div>
                    @endif
                    @if($device->motivo)
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 uppercase font-medium">CategorÃ­a</span>
                        <span class="px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-500 font-bold uppercase text-[9px]">{{ strtoupper(str_replace('_', ' ', $device->motivo)) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Safe Perimeter Status -->
            <div class="rounded-xl p-4 shrink-0 {{ $safePlaces->count() == 0 ? 'bg-slate-800 border border-slate-700' : ($isInsideSafeZone ? 'bg-emerald-500/5 border border-emerald-500/20' : 'bg-amber-500/5 border border-amber-500/20') }}">
                <div class="flex items-center gap-2 mb-2 {{ $safePlaces->count() == 0 ? 'text-slate-500' : ($isInsideSafeZone ? 'text-emerald-500' : 'text-amber-500') }}">
                    <span class="material-icons-round text-sm">{{ $safePlaces->count() == 0 ? 'info' : ($isInsideSafeZone ? 'verified_user' : 'warning') }}</span>
                    <span class="text-[10px] font-bold uppercase tracking-widest">PerÃ­metro Seguro</span>
                </div>
                <p class="text-xs leading-relaxed">
                    @if($safePlaces->count() == 0) Sin zonas seguras configuradas.
                    @elseif($isInsideSafeZone) Dispositivo Seguro. Dentro de: <span class="font-bold border-b border-emerald-500/30">{{ $activeSafeZoneName }}</span>
                    @else âš ï¸ ALERTA: Fuera de perÃ­metro seguro. @endif
                </p>
            </div>

            <!-- Emergency Lock -->
            <button class="w-full py-3 bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/20 rounded-xl transition-all flex items-center justify-center gap-2 shrink-0">
                <span class="material-icons-round text-sm">lock_person</span>
                <span class="text-[10px] font-bold uppercase tracking-widest">Bloqueo de Emergencia</span>
            </button>
        </aside>

        <!-- MAP SECTION -->
        <section class="flex-1 relative rounded-2xl overflow-hidden border border-border-dark bg-[#0d1117] map-grid min-h-[60vh] lg:min-h-0">

            <div id="map-loader" class="absolute inset-0 bg-white/80 dark:bg-[#0d1117]/80 backdrop-blur-sm z-[500] hidden flex flex-col items-center justify-center">
                <div class="w-10 h-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                <span class="text-xs font-mono font-bold text-primary tracking-widest animate-pulse uppercase">Cargando Historial...</span>
            </div>


            <div id="perimeter-helper" class="absolute top-4 right-4 z-[400] hidden">
                <div class="bg-[#1c1e21]/95 backdrop-blur-md border border-[#6CD400] text-white rounded-xl p-4 shadow-xl max-w-xs animate-bounce">
                    <div class="flex items-start gap-2.5">
                        <span class="material-icons-round text-[#6CD400] text-lg mt-0.5">place</span>
                        <div>
                            <h5 class="text-xs font-bold mb-1">AÃ±adir Punto Seguro</h5>
                            <p class="text-[10px] text-slate-400 leading-normal">Haz clic en el mapa para ubicar el centro de tu nueva zona segura.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="safe-place-form-card" class="absolute left-4 right-4 lg:left-1/2 lg:-translate-x-1/2 lg:right-auto bottom-4 z-[400] hidden max-w-sm">
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
                            <input type="text" name="name" required placeholder="Ej. Casa de Abuelos, Escuela" class="w-full bg-slate-50 dark:bg-background-dark border border-slate-200 dark:border-border-dark rounded-xl py-2 px-3 text-xs text-slate-900 dark:text-white placeholder-slate-400 outline-none focus:border-primary transition-all">
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider">Radio (metros)</label>
                                <span id="radius-value" class="text-xs font-mono font-bold text-primary">150m</span>
                            </div>
                            <input type="range" name="radius_meters" id="radius-slider" min="50" max="1000" step="25" value="150" class="w-full accent-primary" oninput="updateCircleRadius(this.value)">
                        </div>
                        <button type="submit" class="w-full bg-primary hover:bg-cyan-600 text-white py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">Guardar PerÃ­metro</button>
                    </form>
                </div>
            </div>

            <div id="map"></div>
        </section>

        <!-- RIGHT PANEL -->
        <aside id="right-drawer" class="fixed inset-y-0 right-0 w-[85vw] max-w-[320px] bg-surface-dark border-l border-border-dark z-50 transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto lg:static lg:w-96 lg:max-w-none lg:translate-x-0 lg:border-none lg:bg-transparent lg:z-auto lg:shrink-0 flex flex-col gap-4 p-4 lg:p-0 lg:pl-0">

            <div class="flex lg:hidden justify-between items-center py-3 border-b border-border-dark mb-2">
                <button onclick="closeAllDrawers()" class="text-slate-500 hover:text-white">
                    <span class="material-icons-round">close</span>
                </button>
                <h2 class="text-sm font-bold text-white uppercase tracking-wider">MÃ©tricas</h2>
            </div>

            <!-- Status bar: fecha + estado en vivo -->
            <div class="flex gap-2 shrink-0">
                <div class="flex-1 bg-background-dark/60 border border-border-dark rounded-xl px-3 py-2.5 flex items-center gap-2.5">
                    <span class="material-icons-round text-primary text-sm">calendar_today</span>
                    <div class="flex flex-col">
                        <span class="text-[9px] uppercase font-bold text-slate-500 leading-none mb-0.5">Historial</span>
                        <span class="text-xs font-bold font-mono leading-none">{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
                    </div>
                </div>
                <div id="live-indicator" class="bg-background-dark/60 border border-border-dark rounded-xl px-3 py-2.5 flex items-center gap-2">
                    <span id="live-dot" class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span id="live-text" class="text-[10px] font-bold uppercase text-amber-500 tracking-wider whitespace-nowrap">Conectando...</span>
                </div>
            </div>


            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark flex flex-col overflow-hidden shrink-0">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">MÃ©tricas del Punto Actual</h3>
                    <span class="material-icons-round text-sm text-slate-400">info</span>
                </div>
                <div class="p-4 space-y-4">
                    <div class="bg-slate-50 dark:bg-background-dark/50 p-4 rounded-lg border border-slate-200 dark:border-border-dark relative">
                        <button onclick="copyToClipboard('{{ $device->latitude }}, {{ $device->longitude }}')" class="absolute right-3 top-3 text-slate-400 hover:text-white transition-colors">
                            <span class="material-icons-round text-sm">content_copy</span>
                        </button>
                        <span class="text-[9px] uppercase font-bold text-slate-500 block mb-2">Coordenadas del Sensor</span>
                        <div class="space-y-1">
                            <div class="flex gap-2"><span class="text-xs font-bold text-slate-500 mono">LAT:</span><span class="text-xs font-bold mono">{{ number_format($device->latitude, 6) }}Â°</span></div>
                            <div class="flex gap-2"><span class="text-xs font-bold text-slate-500 mono">LNG:</span><span class="text-xs font-bold mono">{{ number_format($device->longitude, 6) }}Â°</span></div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] uppercase font-medium text-slate-500">Ãšltima SeÃ±al</span>
                        <span id="live-last-seen" class="text-[10px] font-bold text-primary uppercase">{{ $device->last_seen ? $device->last_seen->diffForHumans() : 'SIN DATOS' }}</span>
                    </div>
                </div>
            </div>

            <!-- Date Selector -->
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark shrink-0">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Historial Activo</h3>
                    <span class="material-icons-round text-sm text-slate-400">calendar_today</span>
                </div>
                <div class="p-3">
                    <select onchange="window.location.href = '?date=' + this.value;" class="w-full bg-slate-100 dark:bg-background-dark border-none outline-none cursor-pointer font-mono font-bold text-xs text-slate-900 dark:text-white rounded-lg p-2">
                        @if(!in_array($selectedDate, $availableDates->toArray()))
                            <option value="{{ $selectedDate }}" selected>{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} (Sin datos)</option>
                        @endif
                        @foreach($availableDates as $date)
                            <option value="{{ $date }}" {{ $date == $selectedDate ? 'selected' : '' }}>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Safe Zones -->
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark shrink-0">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Zonas Seguras ({{ $safePlaces->count() }})</h3>
                    <button id="btn-draw" onclick="toggleDrawingMode()" class="text-[10px] font-bold uppercase text-primary border-b border-primary/30 flex items-center gap-1">
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
                        <form action="{{ route('safe-place.destroy', $place->id) }}" method="POST" onsubmit="return confirm('Â¿Eliminar {{ $place->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                <span class="material-icons-round text-sm">delete_outline</span>
                            </button>
                        </form>
                    </div>
                    @empty
                    <div class="p-4 text-center text-xs text-slate-500 italic">No hay perÃ­metros creados.</div>
                    @endforelse
                </div>
            </div>

            <!-- Movement History -->
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark flex-1 flex flex-col min-h-0 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Movimientos de Hoy</h3>
                    <span class="text-[10px] font-bold uppercase text-primary">Historial</span>
                </div>
                <div id="history-container" class="flex-1 overflow-y-auto p-4 space-y-6 relative"></div>
            </div>
        </aside>

    </main>


    <footer class="h-8 lg:h-10 px-4 lg:px-6 border-t border-border-dark bg-surface-dark flex items-center justify-between shrink-0">
        <div class="flex gap-4">
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-[#6CD400]"></span>
                <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">System Ready</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">Node {{ $device->alias }} Connected</span>
            </div>
        </div>
        <div class="flex gap-4">
            <span class="text-[9px] uppercase tracking-wider text-slate-500 font-medium hidden sm:block">Precision: Â± 2.4m</span>
            <span class="text-[9px] uppercase tracking-wider text-slate-500 font-medium font-mono">v1.0.0</span>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
    <script>
        // â”€â”€ ConfiguraciÃ³n para device-detail.js â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        window.__WAREY_CONFIG__ = {
            lat:          {{ $device->latitude ?? 19.4326 }},
            lng:          {{ $device->longitude ?? -99.1332 }},
            alias:        '{{ $device->alias }}',
            activity:     '{{ $device->activity ?? 'N/A' }}',
            selectedDate: '{{ $selectedDate }}',
            historyUrl:   '{{ route('device.history', $device->id) }}',
            sseUrl:       '{{ route('device.sse', $device->id) }}',
            safePlaces: [
                @foreach($safePlaces as $place)
                {
                    name:          '{{ addslashes($place->name) }}',
                    latitude:      {{ $place->latitude }},
                    longitude:     {{ $place->longitude }},
                    radius_meters: {{ $place->radius_meters }}
                },
                @endforeach
            ]
        };

        // â”€â”€ Drawer helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function openLeftDrawer() {
            var drawer   = document.getElementById('left-drawer');
            var backdrop = document.getElementById('drawers-backdrop');
            drawer.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
            void backdrop.offsetWidth;
            backdrop.classList.remove('opacity-0');
        }

        function openRightDrawer() {
            var drawer   = document.getElementById('right-drawer');
            var backdrop = document.getElementById('drawers-backdrop');
            drawer.classList.remove('translate-x-full');
            backdrop.classList.remove('hidden');
            void backdrop.offsetWidth;
            backdrop.classList.remove('opacity-0');
        }

        function closeAllDrawers() {
            document.getElementById('left-drawer').classList.add('-translate-x-full');
            document.getElementById('right-drawer').classList.add('translate-x-full');
            var backdrop = document.getElementById('drawers-backdrop');
            backdrop.classList.add('opacity-0');
            setTimeout(function() {
                backdrop.classList.add('hidden');
                // Recalculate map size after drawer closes (Leaflet needs this on mobile)
                if (window._wareyMap) window._wareyMap.invalidateSize();
            }, 320);
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text);
        }

        // â”€â”€ Zona segura â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function updateCircleRadius(val) {
            var el = document.getElementById('radius-value');
            if (el) el.innerText = val + 'm';
        }

        function cancelSafePlace() {
            var card = document.getElementById('safe-place-form-card');
            if (card) card.classList.add('hidden');
        }
    </script>
    <script src="{{ asset('js/device-detail.js') }}"></script>
</body>
</html>
