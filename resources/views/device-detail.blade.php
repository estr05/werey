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

                        {{-- Velocidad (id para actualización SSE) --}}
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-slate-500 uppercase">Velocidad</span>
                            <span id="live-speed" class="text-[9px] font-mono text-emerald-400">
                                {{ $device->speed_kmh !== null ? number_format($device->speed_kmh, 1).' km/h' : '—' }}
                            </span>
                        </div>

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

                <!-- Eliminado el Datepicker lateral redundante, ahora vive en el overlay del mapa -->

                <button class="mt-auto bg-red-500/10 border border-red-500/50 text-red-500 w-full py-4 rounded-xl font-bold text-xs uppercase hover:bg-red-500 hover:text-white transition-all flex items-center justify-center gap-2 shrink-0">
                    <span class="material-symbols-outlined text-sm">lock</span> Bloqueo de Emergencia
                </button>
            </div>

            <!-- Columna Central: Mapa Interactivo -->
            <div class="lg:col-span-6 h-full relative">
                <div class="absolute inset-0 bg-black rounded-3xl border border-slate-800 overflow-hidden shadow-2xl">
                    
                    <!-- Overlay de Carga (T5) -->
                    <div id="map-loader" class="absolute inset-0 bg-[#1c1e21]/80 backdrop-blur-sm z-[500] hidden flex flex-col items-center justify-center">
                        <div class="size-10 border-4 border-[#00e5ff]/20 border-t-[#00e5ff] rounded-full animate-spin mb-4"></div>
                        <span class="text-xs font-mono font-bold text-[#00e5ff] tracking-widest animate-pulse">CARGANDO HISTORIAL...</span>
                    </div>
                    
                    <!-- Overlay de Fecha del Historial en el Mapa -->
                    <div class="absolute top-4 left-4 z-[400] flex flex-col gap-2">
                        <div class="bg-[#1c1e21]/90 backdrop-blur-md border border-slate-700 text-slate-300 rounded-xl px-3 py-1.5 text-xs font-bold tracking-wider flex items-center gap-2 shadow-lg focus-within:border-[#00e5ff] transition-colors hover:bg-slate-800">
                            <span class="material-symbols-outlined text-[#00e5ff] text-base">calendar_today</span>
                            <span class="text-slate-400">Historial:</span>
                            <select onchange="window.location.href = '?date=' + this.value;" class="bg-transparent border-none outline-none text-white cursor-pointer font-mono font-bold appearance-none pr-4">
                                @if(!in_array($selectedDate, $availableDates->toArray()))
                                    <option value="{{ $selectedDate }}" class="bg-slate-900 text-slate-500" selected>{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} (Sin datos)</option>
                                @endif
                                @foreach($availableDates as $date)
                                    <option value="{{ $date }}" class="bg-slate-900 text-white" {{ $date == $selectedDate ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined text-slate-500 text-sm -ml-4 pointer-events-none">expand_more</span>
                        </div>
                        {{-- Indicador LIVE SSE --}}
                        <div id="live-indicator" class="bg-[#1c1e21]/90 backdrop-blur-md border border-slate-700 rounded-xl px-3 py-1.5 flex items-center gap-2 shadow-lg self-start">
                            <span id="live-dot" class="size-2 rounded-full bg-slate-600"></span>
                            <span id="live-text" class="text-[10px] font-mono text-slate-500">Conectando...</span>
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
                            <span id="live-last-seen" class="text-[9px] text-[#00e5ff] font-bold flex items-center gap-1">
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
                <div class="bg-[#1c1e21] p-5 rounded-2xl border border-slate-800 flex-1 min-h-[200px] flex flex-col overflow-hidden">
                    <div class="flex justify-between items-center mb-4 shrink-0">
                        <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest font-mono">Movimientos de Hoy</h4>
                        <span class="text-[9px] text-[#00e5ff] font-bold">HISTORIAL</span>
                    </div>
                    <div id="history-container" class="space-y-3 flex-1 overflow-y-auto pr-1 relative">
                        <!-- El contenido se inyecta dinámicamente vía JS -->
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
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:20px;line-height:1;">my_location</span>';
                btn.style.cssText = [
                    'width:36px', 'height:36px', 'display:flex', 'align-items:center',
                    'justify-content:center', 'background:#1c1e21', 'border:none',
                    'color:#00e5ff', 'cursor:pointer', 'border-radius:8px',
                    'transition:background 0.2s'
                ].join(';');
                btn.onmouseenter = function () { btn.style.background = '#005d70'; };
                btn.onmouseleave = function () { btn.style.background = '#1c1e21'; };
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
                var pts   = raw;

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

                // Dibujar línea punteada gris si hubo un Gap/Desconexión
                if (seg.isGapBefore && index > 0 && seg.points.length > 0) {
                    var prevSeg = segs[index - 1];
                    if (prevSeg.points.length > 0) {
                        var p1 = prevSeg.points[prevSeg.points.length - 1];
                        var p2 = seg.points[0];
                        var gapPl = L.polyline([[p1.lat, p1.lng], [p2.lat, p2.lng]], {
                            color: '#475569', weight: 3, dashArray: '4, 8', opacity: 0.6, lineJoin: 'round'
                        }).addTo(map);
                        gapPl.bindTooltip("Pérdida de Señal / Offline", {className: 'text-xs font-mono font-bold text-slate-400 bg-[#131416] border-slate-700'});
                        activeRouteLayers.push(gapPl);
                    }
                }
            });

            if (clean.length > 0 && bounds.isValid()) {
                map.fitBounds(bounds, { padding: [50, 50] });
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
                    <div class="flex items-center justify-center gap-2 py-1 opacity-60">
                        <div class="h-px bg-slate-700 flex-1"></div>
                        <span class="text-[8px] font-mono text-slate-500 bg-[#131416] px-2 rounded-full border border-slate-700">Offline / Gap (${diffMins}m)</span>
                        <div class="h-px bg-slate-700 flex-1"></div>
                    </div>`;
                }

                container.innerHTML += `
                    <div id="${p._segId}-item-${i}" data-seg-id="${p._segId}" onclick="clickTimelineItem('${p._segId}')" class="timeline-item cursor-pointer p-3 rounded-xl border border-white/5 bg-transparent opacity-80 hover:opacity-100 hover:bg-slate-800/40 transition-all">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-white font-bold text-[11px]">${p.label_time}</span>
                            <span class="text-[10px] font-bold font-mono ${colorClass}">${(p.movement_type || p.activity).toUpperCase()}</span>
                        </div>
                        <p class="text-[9px] text-slate-400 font-mono">
                            Bat: ${p.battery}% • Acc: ${p.accuracy ? Math.round(p.accuracy)+'m' : '--'} ${cardinalHtml}
                        </p>
                        <p class="text-[9px] text-slate-500 font-mono mt-0.5">${speedHtml}${motHtml}</p>
                    </div>
                    ${gapHtml}
                `;
            });
        }
        
        // ── Funciones de Sincronización Mapa <-> Timeline ──
        window._activeSegmentId = null;

        function highlightTimelineSegment(segId) {
            document.querySelectorAll('.timeline-item').forEach(el => {
                el.classList.remove('ring-2', 'ring-[#00e5ff]', 'bg-slate-800/80');
            });
            var target = document.querySelector(`.timeline-item[data-seg-id="${segId}"]`);
            if (target) {
                target.classList.add('ring-2', 'ring-[#00e5ff]', 'bg-slate-800/80');
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
                var res = await fetch('{{ route("device.history", $device->id) }}?date=' + encodeURIComponent(date));
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

            sseInstance = new EventSource('{{ route("device.sse", $device->id) }}');

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