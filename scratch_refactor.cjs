const fs = require('fs');
const path = 'c:/webapps/laravel/proyectos_personales/warey/resources/views/device-detail.blade.php';

let content = fs.readFileSync(path, 'utf8');

const htmlNew = `<!DOCTYPE html>
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
                                <option value="{{ $selectedDate }}" class="bg-white dark:bg-surface-dark" selected>{{ \\Carbon\\Carbon::parse($selectedDate)->format('d/m/Y') }} (Sin datos)</option>
                            @endif
                            @foreach($availableDates as $date)
                                <option value="{{ $date }}" class="bg-white dark:bg-surface-dark" {{ $date == $selectedDate ? 'selected' : '' }}>
                                    {{ \\Carbon\\Carbon::parse($date)->format('d/m/Y') }}
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
`;

const jsCenterCtrlOriginal = `                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:20px;line-height:1;">my_location</span>';
                btn.style.cssText = [
                    'width:36px', 'height:36px', 'display:flex', 'align-items:center',
                    'justify-content:center', 'background:#1c1e21', 'border:none',
                    'color:#00e5ff', 'cursor:pointer', 'border-radius:8px',
                    'transition:background 0.2s'
                ].join(';');
                btn.onmouseenter = function () { btn.style.background = '#005d70'; };
                btn.onmouseleave = function () { btn.style.background = '#1c1e21'; };`;
                
const jsCenterCtrlNew = `                btn.innerHTML = '<span class="material-icons-round text-xl">my_location</span>';
                btn.className = 'w-10 h-10 bg-white/80 dark:bg-surface-dark/90 backdrop-blur-md rounded-lg border border-slate-200 dark:border-border-dark shadow-lg flex items-center justify-center hover:text-primary transition-colors text-slate-600 dark:text-slate-300';
                btn.style.cssText = 'cursor:pointer; pointer-events:auto;';
                btn.onmouseenter = null;
                btn.onmouseleave = null;`;

const jsTimelineHtmlOriginal = `                if (isGap) {
                    var diffMins = Math.round((time - new Date(nextP.time).getTime()) / 60000);
                    gapHtml = \`
                    <div class="flex items-center justify-center gap-2 py-1 opacity-60">
                        <div class="h-px bg-slate-700 flex-1"></div>
                        <span class="text-[8px] font-mono text-slate-500 bg-[#131416] px-2 rounded-full border border-slate-700">Offline / Gap (\${diffMins}m)</span>
                        <div class="h-px bg-slate-700 flex-1"></div>
                    </div>\`;
                }

                container.innerHTML += \`
                    <div id="\${p._segId}-item-\${i}" data-seg-id="\${p._segId}" onclick="clickTimelineItem('\${p._segId}')" class="timeline-item cursor-pointer p-3 rounded-xl border border-white/5 bg-transparent opacity-80 hover:opacity-100 hover:bg-slate-800/40 transition-all">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-white font-bold text-[11px]">\${p.label_time}</span>
                            <span class="text-[10px] font-bold font-mono \${colorClass}">\${(p.movement_type || p.activity).toUpperCase()}</span>
                        </div>
                        <p class="text-[9px] text-slate-400 font-mono">
                            Bat: \${p.battery}% • Acc: \${p.accuracy ? Math.round(p.accuracy)+'m' : '--'} \${cardinalHtml}
                        </p>
                        <p class="text-[9px] text-slate-500 font-mono mt-0.5">\${speedHtml}\${motHtml}</p>
                    </div>
                    \${gapHtml}
                \`;`;

const jsTimelineHtmlNew = `                if (isGap) {
                    var diffMins = Math.round((time - new Date(nextP.time).getTime()) / 60000);
                    gapHtml = \`
                    <div class="relative pl-4 border-l-2 border-slate-200 dark:border-border-dark opacity-60 py-2">
                        <div class="flex items-center gap-2">
                            <div class="h-px bg-slate-200 dark:bg-border-dark flex-1"></div>
                            <span class="text-[8px] font-mono text-slate-400 px-2 uppercase font-bold">Offline / Gap (\${diffMins}m)</span>
                            <div class="h-px bg-slate-200 dark:bg-border-dark flex-1"></div>
                        </div>
                    </div>\`;
                }

                var borderClass = colorClass.includes('#6CD400') ? 'border-emerald-500' : 'border-slate-200 dark:border-border-dark';

                container.innerHTML += \`
                    <div id="\${p._segId}-item-\${i}" data-seg-id="\${p._segId}" onclick="clickTimelineItem('\${p._segId}')" class="timeline-item cursor-pointer relative pl-4 border-l-2 \${borderClass} opacity-80 hover:opacity-100 transition-all">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-xs font-bold mono \${colorClass.includes('#6CD400') ? 'text-slate-900 dark:text-white' : 'text-slate-500'}">\${p.label_time}</span>
                            <span class="text-[9px] uppercase font-bold text-slate-400">\${(p.movement_type || p.activity).toUpperCase()}</span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] text-slate-500 mono">Bat: \${p.battery}% • Acc: \${p.accuracy ? Math.round(p.accuracy)+'m' : '--'} \${cardinalHtml}</p>
                            <p class="text-[9px] text-slate-500 mono">\${speedHtml}\${motHtml}</p>
                        </div>
                    </div>
                    \${gapHtml}
                \`;`;

const jsHighlightOriginal = `        function highlightTimelineSegment(segId) {
            document.querySelectorAll('.timeline-item').forEach(el => {
                el.classList.remove('ring-2', 'ring-[#00e5ff]', 'bg-slate-800/80');
            });
            var target = document.querySelector(\`.timeline-item[data-seg-id="\${segId}"]\`);
            if (target) {
                target.classList.add('ring-2', 'ring-[#00e5ff]', 'bg-slate-800/80');
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }`;

const jsHighlightNew = `        function highlightTimelineSegment(segId) {
            document.querySelectorAll('.timeline-item').forEach(el => {
                el.classList.remove('bg-primary/10', 'rounded-r-lg');
            });
            var target = document.querySelector(\`.timeline-item[data-seg-id="\${segId}"]\`);
            if (target) {
                target.classList.add('bg-primary/10', 'rounded-r-lg');
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }`;

// Encontrar la línea del script leaflet
const splitPoint = '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
const parts = content.split(splitPoint);

if(parts.length < 2) {
    console.error("No se encontró el script de leaflet");
    process.exit(1);
}

let newContent = htmlNew + '\\n    ' + splitPoint + parts[1];

newContent = newContent.replace(jsCenterCtrlOriginal, jsCenterCtrlNew);
newContent = newContent.replace(jsTimelineHtmlOriginal, jsTimelineHtmlNew);
newContent = newContent.replace(jsHighlightOriginal, jsHighlightNew);

fs.writeFileSync(path, newContent, 'utf8');
console.log("Reemplazo exitoso");
