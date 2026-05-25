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
              primary: "#06b6d4", // Cyan 500
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

        /* Estilos Mapa */
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

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen overflow-hidden flex flex-col">
    <header class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-border-dark bg-white dark:bg-surface-dark z-10 shrink-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                <span class="material-icons-round text-slate-600 dark:text-slate-300">arrow_back</span>
            </a>
            <div>
                <h1 class="text-xl font-bold tracking-tight uppercase leading-none">{{ $device->alias ?? 'Unit Control Center' }}</h1>
                <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400 font-medium">Rastreo de telemetría en tiempo real</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-primary/30 bg-primary/5 text-[11px] font-semibold text-primary uppercase tracking-wider">
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                Encrypted Link Active
            </div>
            <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center overflow-hidden">
                <span class="material-icons-round text-slate-500">person</span>
            </div>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden p-4 gap-4">
        <!-- Columna Izquierda: Telemetría actual y Estatus -->
        <aside class="w-80 flex flex-col gap-4 overflow-y-auto pr-2 shrink-0">
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark p-5 shrink-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-500">
                            <span class="material-icons-round text-xl">sensors</span>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase text-slate-500 font-bold">Actividad</p>
                            <p class="text-sm font-bold uppercase">{{ strtoupper($device->activity ?? 'UNKNOWN') }}</p>
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

            <!-- Alerta de Zona Segura -->
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

        <!-- Columna Central: Mapa Interactivo -->
        <section class="flex-1 relative rounded-2xl overflow-hidden border border-slate-200 dark:border-border-dark bg-slate-100 dark:bg-[#0d1117] map-grid shadow-lg">
            
            <!-- Overlay de Carga -->
            <div id="map-loader" class="absolute inset-0 bg-white/80 dark:bg-[#0d1117]/80 backdrop-blur-sm z-[500] hidden flex flex-col items-center justify-center">
                <div class="w-10 h-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                <span class="text-xs font-mono font-bold text-primary tracking-widest animate-pulse uppercase">Cargando Historial...</span>
            </div>

            <!-- Overlay de Fecha del Historial en el Mapa -->
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

                {{-- Indicador LIVE SSE --}}
                <div id="live-indicator" class="bg-white/80 dark:bg-surface-dark/90 backdrop-blur-md px-3 py-2 rounded-lg border border-slate-200 dark:border-border-dark shadow-xl flex items-center gap-2">
                    <span id="live-dot" class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span id="live-text" class="text-[10px] font-bold uppercase text-amber-500 tracking-wider">Conectando...</span>
                </div>
            </div>

            <!-- Overlay flotante para agregar punto seguro -->
            <div id="perimeter-helper" class="absolute top-4 right-4 z-[400] hidden">
                <div class="bg-white/90 dark:bg-surface-dark/95 backdrop-blur-md border border-primary text-slate-900 dark:text-white rounded-xl p-4 shadow-xl max-w-xs animate-bounce">
                    <div class="flex items-start gap-2.5">
                        <span class="material-icons-round text-primary text-lg mt-0.5">place</span>
                        <div>
                            <h5 class="text-xs font-bold mb-1">Añadir Punto Seguro</h5>
                            <p class="text-[10px] text-slate-500 leading-normal">Haz clic en el mapa para ubicar el centro de tu nueva zona segura.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario Flotante de Punto Seguro (Se activa tras clic) -->
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

            <!-- Leaflet Map Container -->
            <div id="map"></div>
        </section>

        <!-- Columna Derecha: Zonas Seguras, Coordenadas e Historial de Pings -->
        <aside class="w-96 flex flex-col gap-4 overflow-y-auto pl-2 shrink-0">
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark flex flex-col overflow-hidden shrink-0">
                <div class="p-4 border-b border-slate-200 dark:border-border-dark flex items-center justify-between">
                    <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Métricas del Punto Actual</h3>
                    <span class="material-icons-round text-sm text-slate-400">info</span>
                </div>
                <div class="p-4 space-y-4">
                    <div class="bg-slate-50 dark:bg-background-dark/50 p-4 rounded-lg border border-slate-200 dark:border-border-dark relative group">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-[9px] uppercase font-bold text-slate-500">Coordenadas del Sensor</span>
                            <span onclick="copyToClipboard('{{ $device->latitude }}, {{ $device->longitude }}')" class="material-icons-round text-sm text-slate-400 cursor-pointer hover:text-primary transition-colors">content_copy</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex gap-2">
                                <span class="text-xs font-bold text-slate-500 mono">LAT:</span>
                                <span class="text-xs font-bold mono">{{ number_format($device->latitude ?? 0, 6) }}°</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="text-xs font-bold text-slate-500 mono">LNG:</span>
                                <span class="text-xs font-bold mono">{{ number_format($device->longitude ?? 0, 6) }}°</span>
                            </div>
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
                    <!-- El contenido se inyecta dinámicamente vía JS -->
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
\n    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

        // 3. Capas de Mapa: Dark base + labels separados (CartoDB, gratuito, sin API key)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://carto.com/">CARTO</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_only_labels/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            opacity: 0.85,
            pane: 'shadowPane' // Renderizar labels encima de polylines
        }).addTo(map);

        // T2: Control personalizado — Botón "Centrar en Dispositivo"
        var CenterControl = L.Control.extend({
            options: { position: 'bottomright' },
            onAdd: function () {
                var container = L.DomUtil.create('div', 'leaflet-bar');
                var btn = L.DomUtil.create('button', '', container);
                btn.title = 'Centrar en dispositivo';
                btn.setAttribute('id', 'btn-center-device');
                btn.innerHTML = '<span class="material-icons-round text-xl">my_location</span>';
                btn.className = 'w-10 h-10 bg-white/80 dark:bg-surface-dark/90 backdrop-blur-md rounded-lg border border-slate-200 dark:border-border-dark shadow-lg flex items-center justify-center hover:text-primary transition-colors text-slate-600 dark:text-slate-300';
                btn.style.cssText = 'cursor:pointer; pointer-events:auto;';
                btn.onmouseenter = null;
                btn.onmouseleave = null;
                L.DomEvent.on(btn, 'click', function (e) {
                    L.DomEvent.stopPropagation(e);
                    if (lat && lng) {
                        map.flyTo([lat, lng], 17, { animate: true, duration: 0.7 });
                    }
                });
                return container;
            }
        });
        map.addControl(new CenterControl());

        // ─────────────────────────────────────────────────────────
        // T5+T6: Estilos, funciones de renderizado y carga via fetch
        // ─────────────────────────────────────────────────────────

        // ── Configuración de Confidence (Ajustable) ──
        const CONFIDENCE = {
            HIGH: { maxAccuracy: 20 },
            MEDIUM: { maxAccuracy: 50 }
            // LOW: > 50
        };

        function getConfidenceLevel(p) {
            // Futuro: considerar gaps, velocidad, jitter. Por ahora accuracy base.
            let acc = p.accuracy || 999;
            if (acc <= CONFIDENCE.HIGH.maxAccuracy) return 'HIGH';
            if (acc <= CONFIDENCE.MEDIUM.maxAccuracy) return 'MEDIUM';
            return 'LOW';
        }

        // Estilos base de ruta por tipo de movimiento
        var baseStyles = {
            'WALKING': { color: '#00e5ff' },
            'RUNNING': { color: '#ff0055' },
            'VEHICLE': { color: '#6CD400' },
            'STATIC':  { color: '#888888' },
            'DEFAULT': { color: '#00e5ff' },
        };

        // Modificadores visuales por Confidence
        var confidenceModifiers = {
            'HIGH':   { weight: 6, opacity: 0.95, dashArray: null },
            'MEDIUM': { weight: 4, opacity: 0.65, dashArray: null },
            'LOW':    { weight: 3, opacity: 0.40, dashArray: '4, 8' }
        };

        // Capas activas en el mapa (para limpiar antes de re-renderizar)
        var activeRouteLayers = [];
        var activeStopCluster = null;

        // ── Limpia el mapa antes de cargar un nuevo historial ──
        function clearMapHistory() {
            activeRouteLayers.forEach(function (l) { map.removeLayer(l); });
            activeRouteLayers = [];
            if (activeStopCluster) {
                map.removeLayer(activeStopCluster);
                activeStopCluster = null;
            }
        }

        // ── Filtro de saltos GPS imposibles (>200 km/h) ──
        function isAnomalousJump(p1, p2) {
            var tDiff = (new Date(p2.time) - new Date(p1.time)) / 3600000;
            if (tDiff <= 0) return false;
            var R = 6371;
            var dLat = (p2.lat - p1.lat) * Math.PI / 180;
            var dLon = (p2.lng - p1.lng) * Math.PI / 180;
            var a = Math.sin(dLat/2)*Math.sin(dLat/2) +
                    Math.cos(p1.lat*Math.PI/180)*Math.cos(p2.lat*Math.PI/180)*
                    Math.sin(dLon/2)*Math.sin(dLon/2);
            return (R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))) / tDiff > 200;
        }

        // ── Interpolación Catmull-Rom Spline ──
        // Genera una curva suave que pasa exactamente por todos los puntos GPS.
        // Alpha controla la "tensión": 0.5 = Centripetal (más natural), 0 = Uniforme, 1 = Cord Length.
        function catmullRomSpline(points, samplesPerSegment) {
            if (points.length < 2) return points;
            samplesPerSegment = samplesPerSegment || 12;

            // Duplicar primer y último punto para que la curva empiece y termine exactamente en ellos
            var pts = [points[0]].concat(points).concat([points[points.length - 1]]);

            var result = [];
            for (var i = 1; i < pts.length - 2; i++) {
                var p0 = pts[i - 1], p1 = pts[i], p2 = pts[i + 1], p3 = pts[i + 2];
                for (var t = 0; t <= 1; t += 1 / samplesPerSegment) {
                    var t2 = t * t;
                    var t3 = t2 * t;
                    // Fórmula Catmull-Rom (alpha = 0.5)
                    var lat =
                        0.5 * ((2 * p1[0]) +
                               (-p0[0] + p2[0]) * t +
                               (2*p0[0] - 5*p1[0] + 4*p2[0] - p3[0]) * t2 +
                               (-p0[0] + 3*p1[0] - 3*p2[0] + p3[0]) * t3);
                    var lng =
                        0.5 * ((2 * p1[1]) +
                               (-p0[1] + p2[1]) * t +
                               (2*p0[1] - 5*p1[1] + 4*p2[1] - p3[1]) * t2 +
                               (-p0[1] + 3*p1[1] - 3*p2[1] + p3[1]) * t3);
                    result.push([lat, lng]);
                }
            }
            // Aseguramos que el último punto esté incluido
            result.push(points[points.length - 1]);
            return result;
        }

        // ── Renderiza las polylines de la ruta sobre el mapa ──
        function renderRoute(points) {
            var sorted = [...points].sort(function(a, b) {
                return new Date(a.time) - new Date(b.time);
            });

            // Filtrar anomalías
            var clean = [];
            sorted.forEach(function (p, i) {
                if (i > 0 && isAnomalousJump(clean[clean.length - 1], p)) return;
                clean.push(p);
            });

            // Guardar limpio para bearing inicial del SSE
            cleanTelemetry = clean;

            // Segmentar por tipo de movimiento, confidence Y por GAPS (Desconexiones de más de 5 minutos)
            var segs = [], curSeg = [], curType = null, curConf = null;
            var prevTime = null;

            clean.forEach(function (p) {
                var t = p.movement_type || 'DEFAULT';
                var conf = getConfidenceLevel(p);
                var time = new Date(p.time).getTime();
                var isGap = prevTime && (time - prevTime > 5 * 60000); // 5 min
                
                if (t !== curType || conf !== curConf || isGap) {
                    if (curSeg.length) {
                        segs.push({ id: 'seg-' + new Date(curSeg[0].time).getTime(), type: curType, confidence: curConf, points: curSeg, isGapBefore: isGap });
                    }
                    curSeg = isGap ? [p] : (curSeg.length ? [curSeg[curSeg.length - 1], p] : [p]);
                    curType = t;
                    curConf = conf;
                } else {
                    curSeg.push(p);
                }
                prevTime = time;
                // Assign segId to the point for timeline sync
                p._segId = 'seg-' + new Date(curSeg[0].time).getTime();
            });
            if (curSeg.length) segs.push({ id: 'seg-' + new Date(curSeg[0].time).getTime(), type: curType, confidence: curConf, points: curSeg, isGapBefore: false });

            // Renderizar polylines y accuracy halos
            var bounds = L.latLngBounds();
            segs.forEach(function (seg, index) {
                var base = baseStyles[seg.type] || baseStyles['DEFAULT'];
                var mod = confidenceModifiers[seg.confidence] || confidenceModifiers['LOW'];
                var style = { color: base.color, weight: mod.weight, opacity: mod.opacity, dashArray: mod.dashArray, lineJoin: 'round' };
                
                var raw   = seg.points.map(function (p) { return [p.lat, p.lng]; });

                var pts = raw;
                // Suavizado inteligente con Turf.js para segmentos con más de 2 puntos
                // No suavizamos GAPS, ni aplicamos over-smoothing a tramos rectos.
                if (raw.length >= 3 && seg.confidence !== 'LOW') {
                    try {
                        var line = turf.lineString(seg.points.map(function(p){ return [p.lng, p.lat]; }));
                        // Sharpness conservador para no distorsionar esquinas reales (0.85 recomendado)
                        var curved = turf.bezierSpline(line, { resolution: 10000, sharpness: 0.85 });
                        pts = curved.geometry.coordinates.map(function(c) { return [c[1], c[0]]; });
                    } catch (e) {
                        console.warn("Turf smoothing failed for segment", e);
                        pts = raw;
                    }
                }

                // Render accuracy halos for points
                seg.points.forEach(function(p) {
                    var acc = p.accuracy || 10;
                    if (acc > 15) { // Solo mostrar halo si es relevante
                        var halo = L.circle([p.lat, p.lng], {
                            radius: acc,
                            color: base.color,
                            weight: 0,
                            fillColor: base.color,
                            fillOpacity: 0.1
                        }).addTo(map);
                        activeRouteLayers.push(halo);
                    }
                });

                if (pts.length > 1) {
                    var pl = L.polyline(pts, { ...style, origType: seg.type, origConf: seg.confidence }).addTo(map);
                    
                    // -- Cálculos de Metadata para el Segmento --
                    var startTime = new Date(seg.points[0].time);
                    var endTime = new Date(seg.points[seg.points.length - 1].time);
                    var durationMins = Math.max(1, Math.round((endTime - startTime) / 60000));
                    
                    var totalDistance = 0, totalSpeed = 0, validSpeeds = 0;
                    for (var i = 1; i < seg.points.length; i++) {
                        totalDistance += haversineMeters(seg.points[i-1], seg.points[i]);
                        if (seg.points[i].speed_kmh) {
                            totalSpeed += parseFloat(seg.points[i].speed_kmh);
                            validSpeeds++;
                        }
                    }
                    var avgSpeed = validSpeeds > 0 ? (totalSpeed / validSpeeds).toFixed(1) : 0;
                    var distStr = totalDistance > 1000 ? (totalDistance / 1000).toFixed(2) + ' km' : Math.round(totalDistance) + ' m';
                    
                    var confIcon = seg.confidence === 'HIGH' ? '🟢' : (seg.confidence === 'MEDIUM' ? '🟡' : '🔴');
                    
                    var tooltipHtml = `
                        <div class="text-slate-900 font-sans p-1 min-w-[160px]">
                            <b class="text-xs uppercase text-blue-600 block mb-1">🏁 Tramo ${seg.type}</b>
                            <div class="text-[10px] space-y-0.5 font-bold text-slate-700">
                                <p>${confIcon} Confianza: ${seg.confidence}</p>
                                <p>⏱️ ${startTime.getHours().toString().padStart(2,'0')}:${startTime.getMinutes().toString().padStart(2,'0')} - ${endTime.getHours().toString().padStart(2,'0')}:${endTime.getMinutes().toString().padStart(2,'0')} (${durationMins} min)</p>
                                <p>📏 Distancia: ${distStr}</p>
                                <p>⚡ Vel. Promedio: ${avgSpeed} km/h</p>
                                <p class="text-[8px] text-slate-400 font-mono pt-1">Clic para analizar tramo</p>
                            </div>
                        </div>
                    `;
                    
                    pl.bindTooltip(tooltipHtml, { sticky: true, className: 'shadow-xl rounded-xl border-none' });
                    
                    // -- Eventos de Interacción --
                    pl.on('mouseover', function(e) {
                        this.setStyle({ weight: style.weight + 4, opacity: 1 });
                    });
                    pl.on('mouseout', function(e) {
                        this.setStyle({ weight: style.weight, opacity: style.opacity });
                        if (window._activeSegmentId === seg.id) {
                            this.setStyle({ color: '#fff', weight: style.weight + 2 }); // Keep highlighted if active
                        }
                    });
                    pl.on('click', function(e) {
                        map.fitBounds(this.getBounds(), { padding: [50, 50], animate: true, duration: 0.5 });
                        highlightTimelineSegment(seg.id);
                        highlightMapSegment(seg.id, this);
                    });
                    
                    pl._segId = seg.id; // Almacenar el ID interno para búsquedas
                    activeRouteLayers.push(pl);
                    bounds.extend(pl.getBounds());
                } else if (pts.length === 1) {
                    bounds.extend(pts[0]);
                }

                // Dibujar marcadores de Gap/Desconexión en lugar de la línea recta
                if (seg.isGapBefore && index > 0 && seg.points.length > 0) {
                    var prevSeg = segs[index - 1];
                    if (prevSeg.points.length > 0) {
                        var p1 = prevSeg.points[prevSeg.points.length - 1];
                        var p2 = seg.points[0];
                        
                        // Marcador 1: Señal Perdida (Último punto conocido)
                        var m1 = L.marker([p1.lat, p1.lng], {
                            icon: L.divIcon({
                                className: '',
                                html: `<div style="background:rgba(239,68,68,0.25); border:1.5px solid #ef4444; border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; color:#ef4444; box-shadow:0 0 8px rgba(239,68,68,0.4);">
                                    <span class="material-symbols-outlined" style="font-size:12px; font-weight:bold;">wifi_off</span>
                                </div>`,
                                iconSize: [20, 20],
                                iconAnchor: [10, 10]
                            })
                        }).addTo(map);
                        m1.bindTooltip("Señal Perdida: " + (p1.label_time || ''), {className: 'text-xs text-red-400 bg-slate-950 border-red-500/30 font-bold font-sans'});
                        activeRouteLayers.push(m1);

                        // Marcador 2: Conexión Restaurada (Nuevo punto)
                        var m2 = L.marker([p2.lat, p2.lng], {
                            icon: L.divIcon({
                                className: '',
                                html: `<div style="background:rgba(16,185,129,0.25); border:1.5px solid #10b981; border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; color:#10b981; box-shadow:0 0 8px rgba(16,185,129,0.4);">
                                    <span class="material-symbols-outlined" style="font-size:12px; font-weight:bold;">sensors</span>
                                </div>`,
                                iconSize: [20, 20],
                                iconAnchor: [10, 10]
                            })
                        }).addTo(map);
                        m2.bindTooltip("Señal Recuperada: " + (p2.label_time || ''), {className: 'text-xs text-emerald-400 bg-slate-950 border-emerald-500/30 font-bold font-sans'});
                        activeRouteLayers.push(m2);
                    }
                }
            });

            if (clean.length > 0 && bounds.isValid()) {
                // Volar directamente a la posición más reciente del dispositivo
                var lastPoint = clean[clean.length - 1];
                map.flyTo([lastPoint.lat, lastPoint.lng], 17, { animate: true, duration: 1.0 });
            }
            
            // Actualizar lista visual de historial
            updateHistoryList(clean);
        }

        // ── T6: Icono de parada estática ──
        function buildStopIcon() {
            return L.divIcon({
                className: '',
                html: `<div style="position:relative;display:flex;align-items:center;justify-content:center;">
                    <div style="background:#ffd600;width:14px;height:14px;border:2.5px solid #131416;border-radius:50%;box-shadow:0 0 12px #ffd600;z-index:2;position:absolute;"></div>
                    <div class="pulse-yellow" style="background:#ffd600;width:14px;height:14px;border-radius:50%;position:absolute;"></div>
                    <span class="material-symbols-outlined" style="color:#ffd600;font-size:20px;font-weight:bold;position:absolute;top:-20px;text-shadow:0 0 8px #ffd600;">arrow_downward</span>
                </div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12],
            });
        }

        // ── T6: Renderiza paradas estáticas con clustering ──
        function renderStops(points) {
            // Calcular grupos de paradas
            var stops = [];
            var group = [];

            function processGroup(g) {
                if (!g.length) return;
                var first = g[0], last = g[g.length - 1];
                var durationMins = Math.max(2, Math.round((new Date(last.time) - new Date(first.time)) / 60000));
                var screenMs = 0;
                for (var i = 0; i < g.length - 1; i++) {
                    if (g[i].screen_active) screenMs += (new Date(g[i+1].time) - new Date(g[i].time));
                }
                stops.push({
                    lat: first.lat, lng: first.lng,
                    restingTime: durationMins,
                    screenMinutes: Math.round(screenMs / 60000),
                    battery: last.battery ?? last.battery_level ?? '--',
                    timeLabel: (first.label_time || '') + ' - ' + (last.label_time || ''),
                });
            }

            points.forEach(function (p) {
                if ((p.activity || '').toLowerCase() === 'still') {
                    group.push(p);
                } else {
                    processGroup(group);
                    group = [];
                }
            });
            processGroup(group);

            if (!stops.length) return;

            // T6: Cluster group con colores Warey
            var cluster = L.markerClusterGroup({
                maxClusterRadius: 40,
                showCoverageOnHover: false,
                iconCreateFunction: function (c) {
                    return L.divIcon({
                        html: `<div style="
                            background:rgba(255,214,0,0.15);border:2px solid #ffd600;
                            border-radius:50%;width:36px;height:36px;
                            display:flex;align-items:center;justify-content:center;
                            font-size:11px;font-weight:bold;color:#ffd600;
                        ">${c.getChildCount()}</div>`,
                        iconSize: [36, 36],
                        iconAnchor: [18, 18],
                    });
                },
            });

            stops.forEach(function (stop) {
                var m = L.marker([stop.lat, stop.lng], { icon: buildStopIcon() });
                m.bindPopup(`
                    <div class="text-slate-900 font-sans p-1">
                        <b class="text-xs uppercase text-amber-500 font-bold block mb-1">📍 Parada Estática</b>
                        <div class="text-[10px] space-y-1 font-semibold text-slate-700">
                            <p>⏳ <b>Tiempo reposo:</b> ${stop.restingTime} min</p>
                            <p>📱 <b>Uso de pantalla:</b> ${stop.screenMinutes} min</p>
                            <p>🔋 <b>Batería:</b> ${stop.battery}%</p>
                            <p class="text-[8px] text-slate-400 font-mono pt-1">Hora: ${stop.timeLabel}</p>
                        </div>
                    </div>`);
                cluster.addLayer(m);
            });

            map.addLayer(cluster);
            activeStopCluster = cluster;
        }

        // ── T5: Loader overlay mientras carga el historial ──
        var mapLoader = document.getElementById('map-loader');
        function showLoader() { if (mapLoader) mapLoader.classList.remove('hidden'); }
        function hideLoader() { if (mapLoader) mapLoader.classList.add('hidden'); }

        // Variable limpia disponible para el SSE (bearing inicial)
        var cleanTelemetry = [];
        
        // ── Renderiza dinámicamente la lista de historial ──
        function updateHistoryList(points) {
            var container = document.getElementById('history-container');
            if (!container) return;
            
            container.innerHTML = '';
            if (points.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-slate-600 text-xs italic">Sin transmisiones reportadas para este día.</div>';
                return;
            }
            
            // Revertir para mostrar los más recientes arriba
            var reversed = [...points].reverse();
            // Tomar los últimos 20
            var toShow = reversed.slice(0, 20);
            
            var prevTime = reversed.length > 20 ? new Date(reversed[20].time).getTime() : null;
            
            toShow.forEach(function(p, i) {
                var time = new Date(p.time).getTime();
                var nextP = i < toShow.length - 1 ? toShow[i+1] : null;
                var isGap = false;
                
                if (nextP) {
                    var nTime = new Date(nextP.time).getTime();
                    if (time - nTime > 5 * 60000) isGap = true;
                }
                
                var colorClass = (p.activity === 'moving' || ['WALKING','RUNNING','VEHICLE'].includes(p.movement_type)) ? 'text-[#6CD400]' : 'text-slate-500';
                var speedHtml = p.speed_kmh != null ? `Velocidad: <span class="text-emerald-400">${parseFloat(p.speed_kmh).toFixed(1)} km/h</span>` : '';
                var cardinalHtml = p.cardinal ? ` • Dir: <span class="text-blue-400">${p.cardinal} (${Math.round(p.bearing || 0)}°)</span>` : '';
                var motHtml = p.motivo ? ` • Mot: <span class="text-purple-400">${p.motivo.replace('_', ' ').toUpperCase()}</span>` : '';
                
                var gapHtml = '';
                if (isGap) {
                    var diffMins = Math.round((time - new Date(nextP.time).getTime()) / 60000);
                    gapHtml = `
                    <div class="relative pl-4 border-l-2 border-slate-200 dark:border-border-dark opacity-60 py-2">
                        <div class="flex items-center gap-2">
                            <div class="h-px bg-slate-200 dark:bg-border-dark flex-1"></div>
                            <span class="text-[8px] font-mono text-slate-400 px-2 uppercase font-bold">Offline / Gap (${diffMins}m)</span>
                            <div class="h-px bg-slate-200 dark:bg-border-dark flex-1"></div>
                        </div>
                    </div>`;
                }

                var borderClass = colorClass.includes('#6CD400') ? 'border-emerald-500' : 'border-slate-200 dark:border-border-dark';

                container.innerHTML += `
                    <div id="${p._segId}-item-${i}" data-seg-id="${p._segId}" onclick="clickTimelineItem('${p._segId}')" class="timeline-item cursor-pointer relative pl-4 border-l-2 ${borderClass} opacity-80 hover:opacity-100 transition-all">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-xs font-bold mono ${colorClass.includes('#6CD400') ? 'text-slate-900 dark:text-white' : 'text-slate-500'}">${p.label_time}</span>
                            <span class="text-[9px] uppercase font-bold text-slate-400">${(p.movement_type || p.activity).toUpperCase()}</span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] text-slate-500 mono">Bat: ${p.battery}% • Acc: ${p.accuracy ? Math.round(p.accuracy)+'m' : '--'} ${cardinalHtml}</p>
                            <p class="text-[9px] text-slate-500 mono">${speedHtml}${motHtml}</p>
                        </div>
                    </div>
                    ${gapHtml}
                `;
            });
        }
        
        // ── Funciones de Sincronización Mapa <-> Timeline ──
        window._activeSegmentId = null;

        function highlightTimelineSegment(segId) {
            document.querySelectorAll('.timeline-item').forEach(el => {
                el.classList.remove('bg-primary/10', 'rounded-r-lg');
            });
            var target = document.querySelector(`.timeline-item[data-seg-id="${segId}"]`);
            if (target) {
                target.classList.add('bg-primary/10', 'rounded-r-lg');
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function highlightMapSegment(segId, layerInstance = null) {
            window._activeSegmentId = segId;
            activeRouteLayers.forEach(pl => {
                if (pl._segId) {
                    // Restaurar estilo original
                    if (pl.options.origConf) {
                        var base = baseStyles[pl.options.origType || 'DEFAULT'];
                        var mod = confidenceModifiers[pl.options.origConf || 'LOW'];
                        pl.setStyle({ color: base.color, weight: mod.weight, opacity: mod.opacity });
                    }
                }
            });
            if (layerInstance) {
                layerInstance.setStyle({ color: '#ffffff', weight: layerInstance.options.weight + 2, opacity: 1 });
            } else {
                var pl = activeRouteLayers.find(l => l._segId === segId);
                if (pl) {
                    pl.setStyle({ color: '#ffffff', weight: pl.options.weight + 2, opacity: 1 });
                    map.fitBounds(pl.getBounds(), { padding: [50, 50], animate: true, duration: 0.5 });
                }
            }
        }

        function clickTimelineItem(segId) {
            highlightTimelineSegment(segId);
            highlightMapSegment(segId);
        }

        // ── T5: Fetch del historial desde la API (no Blade) ──
        async function loadHistory(date) {
            showLoader();
            clearMapHistory();

            try {
                var res = await fetch('{{ route("device.history", $device) }}?date=' + encodeURIComponent(date));
                if (!res.ok) throw new Error('HTTP ' + res.status);
                var json = await res.json();

                if (json.success && json.points && json.points.length > 0) {
                    renderRoute(json.points);
                    renderStops(json.points);
                }
            } catch (err) {
                console.warn('[Warey] Error cargando historial:', err);
            } finally {
                hideLoader();
            }
        }

        // Cargar historial inicial al abrir la página
        document.addEventListener('DOMContentLoaded', function () {
            loadHistory('{{ $selectedDate }}');
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

        // T3: Calcular bearing (dirección de movimiento) entre los 2 últimos puntos GPS
        function calculateBearing(p1, p2) {
            var lat1 = p1.lat * Math.PI / 180;
            var lat2 = p2.lat * Math.PI / 180;
            var dLon = (p2.lng - p1.lng) * Math.PI / 180;
            var y = Math.sin(dLon) * Math.cos(lat2);
            var x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon);
            return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
        }

        function haversineMeters(p1, p2) {
            var R = 6371000;
            var dLat = (p2.lat - p1.lat) * Math.PI / 180;
            var dLon = (p2.lng - p1.lng) * Math.PI / 180;
            var a = Math.sin(dLat/2) * Math.sin(dLat/2)
                  + Math.cos(p1.lat * Math.PI / 180) * Math.cos(p2.lat * Math.PI / 180)
                  * Math.sin(dLon/2) * Math.sin(dLon/2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        // Determinar bearing. Usar la de la DB si existe, si no calcularla
        var currentBearing = 0;
        var showArrow = false;
        var currentActivity = '{{ $device->activity ?? "still" }}';

        if (cleanTelemetry.length > 0) {
            var pLast = cleanTelemetry[cleanTelemetry.length - 1];
            
            if (pLast.bearing !== null && pLast.bearing !== undefined) {
                currentBearing = pLast.bearing;
                showArrow = (currentActivity !== 'still' && pLast.speed_kmh > 1);
            } else if (cleanTelemetry.length >= 2) {
                // Fallback a cálculo trigonométrico
                var pPrev = cleanTelemetry[cleanTelemetry.length - 2];
                var distBetween = haversineMeters(pPrev, pLast);
                if (distBetween > 5) {
                    currentBearing = calculateBearing(pPrev, pLast);
                    showArrow = (currentActivity !== 'still');
                }
            }
        }

        // 10. Icono de ubicación actual con bearing indicator
        function buildUnitIcon(bearing, isMoving) {
            var arrowHtml = isMoving
                ? `<div style="
                    position:absolute; top:-11px; left:50%;
                    transform:translateX(-50%);
                    width:0; height:0;
                    border-left:5px solid transparent;
                    border-right:5px solid transparent;
                    border-bottom:11px solid #6CD400;
                    filter:drop-shadow(0 0 4px #6CD400);
                  "></div>`
                : '';

            return L.divIcon({
                className: '',
                html: `<div style="
                    position:relative; width:28px; height:28px;
                    transform:rotate(${bearing}deg);
                    display:flex; align-items:center; justify-content:center;
                ">
                    ${arrowHtml}
                    <div style="
                        background:#6CD400; width:22px; height:22px;
                        border:3.5px solid #ffffff; border-radius:50%;
                        box-shadow:0 0 20px rgba(108,212,0,0.8);
                        position:absolute;
                    "></div>
                    <div style="
                        background:#6CD400; width:22px; height:22px;
                        border-radius:50%; animation:pulse 2s infinite;
                        opacity:0.45; position:absolute;
                    "></div>
                </div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });
        }

        var unitIcon = buildUnitIcon(currentBearing, showArrow);

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

        // ─────────────────────────────────────────────────────────
        // T7-frontend: SSE en tiempo real para la vista del device
        // ─────────────────────────────────────────────────────────
        var liveDot       = document.getElementById('live-dot');
        var liveText      = document.getElementById('live-text');
        var liveMarker    = null;   // marker dinámico (único owner del punto actual)
        var autoFollow    = true;   // seguir al device automáticamente
        var sseReconnects = 0;
        var sseInstance   = null;

        // Detener auto-follow si el usuario hace pan manualmente
        map.on('dragstart', function () { autoFollow = false; });

        // ── Animación suave del marker entre dos coordenadas ──
        function animateMarker(marker, newLatLng, durationMs) {
            var start    = marker.getLatLng();
            var t0       = performance.now();

            function easeInOut(t) {
                return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
            }

            function step(now) {
                var t   = Math.min((now - t0) / durationMs, 1);
                var e   = easeInOut(t);
                var lat = start.lat + (newLatLng.lat - start.lat) * e;
                var lng = start.lng + (newLatLng.lng - start.lng) * e;
                marker.setLatLng([lat, lng]);
                if (t < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }

        // ── Actualizar indicador LIVE ──
        function setLiveStatus(state) {
            var states = {
                connecting: { dot: 'bg-amber-400 animate-pulse', text: 'Conectando...', color: 'text-amber-400' },
                live:       { dot: 'bg-emerald-500 animate-pulse', text: '● LIVE',       color: 'text-emerald-400' },
                error:      { dot: 'bg-red-500',                   text: 'Sin señal',    color: 'text-red-400'    },
            };
            var s = states[state] || states.error;
            liveDot.className  = 'size-2 rounded-full ' + s.dot;
            liveText.className = 'text-[10px] font-mono ' + s.color;
            liveText.textContent = s.text;
        }

        // ── Procesar evento 'position' del SSE ──
        function handlePosition(data) {
            if (!data.latitude || !data.longitude) return;

            var newLatLng = L.latLng(data.latitude, data.longitude);
            var isMoving  = (data.activity !== 'still');

            // Usar bearing emitido por SSE, o mantener actual
            var bearing = currentBearing;
            if (data.bearing !== null && data.bearing !== undefined) {
                bearing = data.bearing;
            } else if (liveMarker) {
                // Fallback cálculo
                var prev = liveMarker.getLatLng();
                var dist = haversineMeters(
                    { lat: prev.lat, lng: prev.lng },
                    { lat: data.latitude, lng: data.longitude }
                );
                if (dist > 5) {
                    bearing = calculateBearing(
                        { lat: prev.lat, lng: prev.lng },
                        { lat: data.latitude, lng: data.longitude }
                    );
                }
            }
            currentBearing = bearing; // actualizar global

            var newIcon = buildUnitIcon(bearing, isMoving);

            if (!liveMarker) {
                // Primera posición: crear marker
                liveMarker = L.marker(newLatLng, { icon: newIcon }).addTo(map);
                liveMarker.bindPopup('<b class="text-slate-900">{{ $device->alias }} (En vivo)</b>');
            } else {
                // Actualizar icono y animar hacia nueva posición
                liveMarker.setIcon(newIcon);
                animateMarker(liveMarker, newLatLng, 2000);
            }

            // Auto-follow: centrar el mapa suavemente si está activo
            if (autoFollow) {
                map.panTo(newLatLng, { animate: true, duration: 1.0 });
            }

            // Actualizar sidebar sin recargar página
            var speedEl    = document.getElementById('live-speed');
            var lastSeenEl = document.getElementById('live-last-seen');

            if (speedEl) {
                speedEl.textContent = data.speed_kmh != null
                    ? parseFloat(data.speed_kmh).toFixed(1) + ' km/h'
                    : '—';
            }
            if (lastSeenEl) {
                var inner = lastSeenEl.querySelector('span');
                if (inner) {
                    lastSeenEl.textContent = '';
                    lastSeenEl.appendChild(inner);
                    lastSeenEl.append(' ' + (data.last_seen || '—'));
                } else {
                    lastSeenEl.textContent = data.last_seen || '—';
                }
            }

            setLiveStatus('live');
        }

        // ── Conectar al SSE con backoff exponencial ──
        function connectDeviceSSE() {
            setLiveStatus('connecting');

            if (sseInstance) {
                sseInstance.close();
            }

            sseInstance = new EventSource('{{ route("device.sse", $device) }}');

            sseInstance.addEventListener('position', function (e) {
                try {
                    sseReconnects = 0;
                    handlePosition(JSON.parse(e.data));
                } catch (err) {
                    console.warn('[Warey SSE] parse error:', err);
                }
            });

            sseInstance.addEventListener('heartbeat', function () {
                // La conexión sigue viva aunque no haya movimiento
                if (liveMarker) setLiveStatus('live');
            });

            sseInstance.onopen = function () {
                sseReconnects = 0;
                setLiveStatus('live');
            };

            sseInstance.onerror = function () {
                sseReconnects++;
                setLiveStatus('error');
                sseInstance.close();

                // Backoff: 3s, 6s, 12s, 24s, máx 30s
                var delay = Math.min(3000 * Math.pow(2, sseReconnects - 1), 30000);
                setTimeout(connectDeviceSSE, delay);
            };
        }

        // Iniciar SSE al cargar la página
        document.addEventListener('DOMContentLoaded', connectDeviceSSE);
    </script>
</body>
</html>