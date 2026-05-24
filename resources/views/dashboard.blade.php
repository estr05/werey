<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Where are You? | Global Fleet Overview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <style>
        body { font-family: 'Epilogue', sans-serif; }
        /* Efecto de brillo para el botón de detalles */
        .btn-manage:hover {
            box-shadow: 0 0 15px rgba(0, 93, 112, 0.4);
        }
    </style>
</head>
<body class="bg-[#131416] text-slate-100 min-h-screen">

    <div class="p-8">
        <header class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-black tracking-tighter uppercase italic text-white">Warey</h1>
                <p class="text-[#8dc3ce] text-xs font-bold tracking-widest uppercase">Telemetry Control Node • Bienvenido, {{ Auth::user()->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openLinkModal()" 
                        class="bg-[#005d70] hover:bg-[#007b94] text-white px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all hover:shadow-[0_0_15px_rgba(0,93,112,0.4)] flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">add_to_home_screen</span> Vincular Teléfono
                </button>
                
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">logout</span> Salir
                    </button>
                </form>
            </div>
        </header>

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-xs rounded-2xl p-5 mb-10">
                <div class="flex items-center gap-2 mb-2 font-bold">
                    <span class="material-symbols-outlined text-sm">error</span>
                    Ocurrió un problema:
                </div>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs rounded-2xl p-5 mb-10">
                <div class="flex items-center gap-2 font-bold">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-10" id="stats-grid">
            <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Total Fleet</p>
                <p class="text-3xl font-black text-white" id="stat-total">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Moving Now</p>
                <p class="text-3xl font-black text-[#6CD400]" id="stat-moving">{{ $stats['moving'] }}</p>
            </div>
            <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Online</p>
                <p class="text-3xl font-black text-[#005d70]" id="stat-online">{{ $stats['online'] }}</p>
            </div>
            <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Alerts</p>
                <p class="text-3xl font-black text-red-500" id="stat-alerts">{{ $stats['alerts'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="devices-grid">
            @forelse($devices as $device)
            @php $isPending = is_null($device->last_seen); @endphp
            <div class="bg-[#1c1e21] rounded-2xl border {{ $isPending ? 'border-slate-700 border-dashed' : 'border-slate-800 hover:border-[#005d70]/50' }} p-6 transition-all flex flex-col">
                <div class="flex justify-between items-start mb-6">
                    <div class="bg-slate-800 size-12 rounded-xl flex items-center justify-center {{ $isPending ? 'text-slate-600' : 'text-[#005d70]' }}">
                        <span class="material-symbols-outlined">
                            {{ $isPending ? 'phonelink_off' : ($device->activity == 'moving' ? 'navigation' : 'smartphone') }}
                        </span>
                    </div>
                    @if($isPending)
                        <span class="bg-amber-500/10 text-amber-400 px-3 py-1 rounded-full text-[10px] font-black uppercase flex items-center gap-1.5">
                            <span class="size-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            En espera
                        </span>
                    @else
                        <span class="{{ $device->activity == 'moving' ? 'bg-[#6CD400]/20 text-[#6CD400]' : 'bg-slate-800 text-slate-500' }} px-3 py-1 rounded-full text-[10px] font-black uppercase flex items-center gap-1.5">
                            <span class="size-1.5 rounded-full {{ $device->activity == 'moving' ? 'bg-[#6CD400] animate-pulse' : 'bg-slate-500' }}"></span>
                            {{ $device->activity }}
                        </span>
                    @endif
                </div>

                <h3 class="font-extrabold text-xl mb-1 truncate {{ $isPending ? 'text-slate-400' : 'text-white' }}">{{ $device->alias }}</h3>
                <p class="text-xs text-slate-500 mb-6 font-medium">ID: {{ $device->identifier }}</p>

                @if($isPending)
                    {{-- Estado pendiente: teléfono aún no conectado --}}
                    <div class="flex-1 flex flex-col items-center justify-center py-4 gap-3 text-center mb-6">
                        <span class="material-symbols-outlined text-3xl text-slate-600">pending</span>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Abre la app móvil e ingresa el código<br>
                            <span class="font-mono text-amber-400 font-bold">{{ $device->identifier }}</span><br>
                            para activar el dispositivo.
                        </p>
                    </div>
                @else
                    {{-- Estado activo: mostrando datos reales --}}
                    <div class="space-y-4 mb-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-slate-400">
                                <span class="material-symbols-outlined text-lg">
                                    {{ $device->is_charging ? 'battery_charging_full' : 'battery_horiz_075' }}
                                </span>
                                <span class="text-sm font-bold">Battery</span>
                            </div>
                            <span class="text-sm font-black text-white">{{ $device->battery_level ?? '--' }}%</span>
                        </div>
                        <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div class="h-full {{ ($device->battery_level ?? 100) < 20 ? 'bg-red-500' : 'bg-[#6CD400]' }}"
                                 style="width: {{ $device->battery_level ?? 0 }}%"></div>
                        </div>
                        
                        <div class="flex justify-between items-center pt-2">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Network</span>
                            <span class="text-[10px] font-mono text-[#005d70] bg-[#005d70]/10 px-2 py-0.5 rounded">
                                {{ strtoupper($device->connection_type ?? 'Offline') }}
                            </span>
                        </div>

                        {{-- Señal — mostrar solo si hay datos --}}
                        @if($device->signal_strength !== null)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Signal</span>
                            <div class="flex items-end gap-0.5">
                                @for($i = 0; $i < 4; $i++)
                                    <span class="w-1.5 rounded-sm {{ $i < $device->signal_strength ? 'bg-[#6CD400]' : 'bg-slate-700' }}" style="height: {{ 8 + $i * 5 }}px"></span>
                                @endfor
                            </div>
                        </div>
                        @endif

                        {{-- Internet --}}
                        @if($device->has_internet !== null)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Internet</span>
                            <span class="text-[10px] font-mono {{ $device->has_internet ? 'text-emerald-400' : 'text-red-400' }} px-2 py-0.5 rounded {{ $device->has_internet ? 'bg-emerald-500/10' : 'bg-red-500/10' }}">
                                {{ $device->has_internet ? 'ONLINE' : 'OFFLINE' }}
                            </span>
                        </div>
                        @endif

                        {{-- Estado de rastreo --}}
                        @if($device->tracking_state)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Tracking</span>
                            <span class="text-[9px] font-mono {{ str_contains($device->tracking_state, 'UNSAFE') ? 'text-amber-400 bg-amber-500/10' : 'text-emerald-400 bg-emerald-500/10' }} px-2 py-0.5 rounded">
                                {{ str_replace('_', ' ', $device->tracking_state) }}
                            </span>
                        </div>
                        @endif

                        {{-- Estado de actividad --}}
                        @if($device->activity_status)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Estado</span>
                            <span class="text-[9px] font-mono text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded">
                                {{ $device->activity_status }}
                            </span>
                        </div>
                        @endif

                        {{-- Velocidad --}}
                        @if($device->speed_kmh !== null)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Velocidad</span>
                            <span class="text-[9px] font-mono text-emerald-400">
                                {{ number_format($device->speed_kmh, 1) }} km/h
                            </span>
                        </div>
                        @endif

                        {{-- Intervalo --}}
                        @if($device->intervalo_aplicado)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Intervalo</span>
                            <span class="text-[9px] font-mono text-cyan-400">
                                Cada {{ $device->intervalo_aplicado }}s
                            </span>
                        </div>
                        @endif

                        {{-- Motivo --}}
                        @if($device->motivo)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Motivo</span>
                            <span class="text-[9px] font-mono text-purple-400">
                                {{ strtoupper(str_replace('_', ' ', $device->motivo)) }}
                            </span>
                        </div>
                        @endif
                    </div>
                @endif
                
                <div class="text-[10px] {{ $isPending ? 'text-amber-500/50' : 'text-slate-600' }} font-mono mb-6 italic">
                    {{ $isPending ? 'ESPERANDO PRIMERA CONEXIÓN...' : 'LAST SYNC: ' . $device->last_seen->diffForHumans() }}
                </div>

                <div class="mt-auto flex items-center gap-3">
                    @if(!$isPending)
                        <a href="{{ route('device.show', $device->id) }}"
                           class="btn-manage flex-1 bg-[#005d70] text-white text-center py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all hover:brightness-110">
                            Manage Node
                        </a>
                    @else
                        <span class="flex-1 bg-slate-800 text-slate-600 text-center py-3 rounded-xl text-xs font-bold uppercase tracking-wider cursor-not-allowed">
                            Teléfono en Espera
                        </span>
                    @endif
                    
                    <form action="{{ route('device.destroy', $device->id) }}" method="POST"
                          onsubmit="return confirm('¿Confirmas la desconexión total del nodo {{ $device->alias }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="bg-red-500/10 text-red-500 p-3 rounded-xl hover:bg-red-500 hover:text-white transition-all">
                            <span class="material-symbols-outlined text-xl">delete</span>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="col-span-full bg-[#1c1e21] border border-dashed border-slate-800 rounded-3xl p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-slate-600 mb-4">cell_tower</span>
                <h3 class="text-lg font-bold text-white mb-2">No hay dispositivos vinculados</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-6">Vincula un teléfono para comenzar a recibir información de telemetría y ubicación en tiempo real.</p>
                <button onclick="openLinkModal()" 
                        class="bg-[#005d70] hover:bg-[#007b94] text-white px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                    Vincular Primer Teléfono
                </button>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Modal de Vinculación de Nuevo Teléfono -->
    <div id="link-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="bg-[#1c1e21] border border-slate-800 rounded-3xl p-8 max-w-md w-full mx-4 shadow-2xl relative">
            <button onclick="closeLinkModal()" class="absolute top-4 right-4 text-slate-500 hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>

            <div class="flex items-center gap-3 mb-6">
                <span class="material-symbols-outlined text-[#00e5ff] text-3xl">add_to_home_screen</span>
                <div>
                    <h3 class="text-xl font-bold text-white">Vincular Teléfono</h3>
                    <p class="text-[10px] text-[#8dc3ce] font-bold tracking-wider uppercase">Fase de Desarrollo</p>
                </div>
            </div>

            <p class="text-xs text-slate-400 mb-6 leading-relaxed bg-slate-900/60 p-4 rounded-xl border border-white/5 font-medium">
                ⚙️ <span class="font-bold text-white">Modo Desarrollo:</span> El ID único se genera automáticamente. Cópialo y pégalo en la app móvil Flutter para sincronizar el canal de telemetría y habilitar el rastreo.
            </p>

            <form action="{{ route('device.store') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label for="alias" class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Alias del Dispositivo</label>
                    <input type="text" name="alias" id="alias" required
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl py-3 px-4 text-sm text-white placeholder-slate-600 outline-none focus:border-[#00e5ff] transition-all"
                           placeholder="Ej. Celular de Mamá, iPhone de Carlos">
                </div>

                <div>
                    <label for="identifier" class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Identificador Único (Auto-generado)</label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="identifier" id="identifier" readonly required
                               class="flex-1 bg-slate-950 border border-slate-700 rounded-xl py-3 px-4 text-sm text-[#00e5ff] outline-none font-mono tracking-widest cursor-default select-all"
                               placeholder="Generando...">
                        <button type="button" id="copy-btn" onclick="copyIdentifier()"
                                title="Copiar ID"
                                class="flex-shrink-0 bg-slate-800 hover:bg-[#005d70] border border-slate-700 text-slate-400 hover:text-white p-3 rounded-xl transition-all">
                            <span id="copy-icon" class="material-symbols-outlined text-xl">content_copy</span>
                        </button>
                    </div>
                    <p id="copy-feedback" class="text-[10px] text-emerald-400 font-bold mt-1.5 hidden">✓ ID copiado al portapapeles</p>
                </div>

                <div class="text-[10px] text-slate-500 font-mono italic">
                    * Límite actual de 3 dispositivos por cuenta de usuario.
                </div>

                <button type="submit" 
                        class="w-full bg-[#005d70] hover:bg-[#007b94] text-white py-3.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                    Sincronizar y Vincular
                </button>
            </form>
        </div>
    </div>

    <!-- Indicador de estado de sincronización en vivo -->
    <div id="sync-indicator" class="fixed bottom-4 right-4 z-50 flex items-center gap-2 bg-[#1c1e21]/90 backdrop-blur-sm border border-slate-800 rounded-full px-4 py-2 shadow-2xl transition-all">
        <span id="sync-dot" class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span id="sync-text" class="text-[10px] font-mono text-slate-400">Sincronizado</span>
        <span id="sync-latency" class="text-[9px] font-mono text-slate-600"></span>
        <svg id="sync-sparkline" class="hidden md:block" width="60" height="18" viewBox="0 0 60 18" style="flex-shrink: 0; opacity: 0.6;"></svg>
        <span id="sync-time" class="text-[9px] font-mono text-slate-600"></span>
    </div>

    <script>
        // Genera un ID único con formato WRY-XXXX-XXXX
        function generateWareyId() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            const rand = (n) => Array.from({length: n}, () => chars[Math.floor(Math.random() * chars.length)]).join('');
            return `WRY-${rand(4)}-${rand(4)}`;
        }

        function openLinkModal() {
            document.getElementById('identifier').value = generateWareyId();
            document.getElementById('copy-feedback').classList.add('hidden');
            document.getElementById('copy-icon').textContent = 'content_copy';
            document.getElementById('link-modal').classList.remove('hidden');
        }

        function closeLinkModal() {
            document.getElementById('link-modal').classList.add('hidden');
        }

        function copyIdentifier() {
            const idValue = document.getElementById('identifier').value;
            if (!idValue) return;

            navigator.clipboard.writeText(idValue).then(() => {
                document.getElementById('copy-icon').textContent = 'check_circle';
                document.getElementById('copy-feedback').classList.remove('hidden');
                document.getElementById('copy-btn').classList.add('bg-[#005d70]', 'text-white');

                setTimeout(() => {
                    document.getElementById('copy-icon').textContent = 'content_copy';
                    document.getElementById('copy-feedback').classList.add('hidden');
                    document.getElementById('copy-btn').classList.remove('bg-[#005d70]', 'text-white');
                }, 2500);
            }).catch(() => {
                document.getElementById('identifier').select();
                document.execCommand('copy');
            });
        }

        // --- Sistema de sincronización en vivo vía SSE (Server-Sent Events) ---
        const syncDot = document.getElementById('sync-dot');
        const syncText = document.getElementById('sync-text');
        const syncTime = document.getElementById('sync-time');
        const syncLatency = document.getElementById('sync-latency');
        const syncSparkline = document.getElementById('sync-sparkline');
        let reconnectAttempts = 0;
        const MAX_RECONNECT_BACKOFF = 30000; // 30s máximo entre reintentos
        const latencyHistory = [];
        const SPARKLINE_MAX = 30;
        const SPARK_W = 60, SPARK_H = 18, SPARK_PAD = 2;

        function renderSparkline() {
            const values = latencyHistory.slice(-SPARKLINE_MAX);
            if (values.length < 2) return;

            const min = Math.min(...values);
            const max = Math.max(...values);
            const range = max - min || 1;
            const drawW = SPARK_W - SPARK_PAD * 2;
            const drawH = SPARK_H - SPARK_PAD * 2;

            const points = values.map((v, i) => {
                const x = SPARK_PAD + (i / (values.length - 1)) * drawW;
                const y = SPARK_PAD + drawH - ((v - min) / range) * drawH;
                return `${x.toFixed(1)},${y.toFixed(1)}`;
            }).join(' ');

            // Elegir color basado en la última latencia (la más reciente -> derecha)
            const last = values[values.length - 1];
            const color = last < 200 ? '#6CD400' : last < 500 ? '#fbbf24' : '#ef4444';

            syncSparkline.innerHTML = `
                <polyline fill="none" stroke="${color}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                          points="${points}" />
                <!-- Fade fill below the line -->
                <polyline fill="${color}" fill-opacity="0.08"
                          points="${SPARK_PAD.toFixed(1)},${(SPARK_H - SPARK_PAD).toFixed(1)} ${points} ${(SPARK_W - SPARK_PAD).toFixed(1)},${(SPARK_H - SPARK_PAD).toFixed(1)}" />
            `;
        }

        function updateLatency(serverTimeIso) {
            if (!serverTimeIso) return;
            try {
                const serverMs = new Date(serverTimeIso).getTime();
                if (isNaN(serverMs)) return;
                const latency = Date.now() - serverMs;
                syncLatency.textContent = `${latency}ms`;
                syncLatency.className = latency < 200
                    ? 'text-[9px] font-mono text-emerald-500'
                    : latency < 500
                        ? 'text-[9px] font-mono text-amber-400'
                        : 'text-[9px] font-mono text-red-500';

                // Almacenar y renderizar sparkline
                latencyHistory.push(latency);
                if (latencyHistory.length > SPARKLINE_MAX * 2) {
                    // Poda periódica para evitar crecimiento infinito
                    latencyHistory.splice(0, latencyHistory.length - SPARKLINE_MAX);
                }
                renderSparkline();
            } catch (err) {
                console.warn('Latency calc error:', err);
            }
        }

        function updateSyncIndicator(status, message) {
            if (status === 'connected') {
                syncDot.className = 'size-2 rounded-full bg-emerald-500 animate-pulse';
                syncText.textContent = message || 'Sincronizado';
                syncText.className = 'text-[10px] font-mono text-emerald-400';
                syncTime.textContent = new Date().toLocaleTimeString();
                reconnectAttempts = 0;
            } else if (status === 'connecting') {
                syncDot.className = 'size-2 rounded-full bg-amber-400 animate-pulse';
                syncText.textContent = message || 'Conectando...';
                syncText.className = 'text-[10px] font-mono text-amber-400';
            } else if (status === 'error') {
                syncDot.className = 'size-2 rounded-full bg-red-500';
                syncText.textContent = message || 'Error de conexión';
                syncText.className = 'text-[10px] font-mono text-red-400';
                syncTime.textContent = `Reintento ${reconnectAttempts}`;
            }
        }

        function renderDeviceCard(device) {
            const isPending = device.is_pending;
            const activity = device.activity || 'unknown';
            const isMoving = activity === 'moving';
            const batteryLevel = device.battery_level;
            const isCharging = device.is_charging;
            const connectionType = device.connection_type || 'Offline';
            const lastSeen = device.last_seen || 'ESPERANDO PRIMERA CONEXIÓN...';

            return `
            <div class="bg-[#1c1e21] rounded-2xl border ${isPending ? 'border-slate-700 border-dashed' : 'border-slate-800 hover:border-[#005d70]/50'} p-6 transition-all flex flex-col">
                <div class="flex justify-between items-start mb-6">
                    <div class="bg-slate-800 size-12 rounded-xl flex items-center justify-center ${isPending ? 'text-slate-600' : 'text-[#005d70]'}">
                        <span class="material-symbols-outlined">${isPending ? 'phonelink_off' : (isMoving ? 'navigation' : 'smartphone')}</span>
                    </div>
                    ${isPending
                        ? `<span class="bg-amber-500/10 text-amber-400 px-3 py-1 rounded-full text-[10px] font-black uppercase flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-amber-400 animate-pulse"></span> En espera</span>`
                        : `<span class="${isMoving ? 'bg-[#6CD400]/20 text-[#6CD400]' : 'bg-slate-800 text-slate-500'} px-3 py-1 rounded-full text-[10px] font-black uppercase flex items-center gap-1.5"><span class="size-1.5 rounded-full ${isMoving ? 'bg-[#6CD400] animate-pulse' : 'bg-slate-500'}"></span> ${activity}</span>`
                    }
                </div>

                <h3 class="font-extrabold text-xl mb-1 truncate ${isPending ? 'text-slate-400' : 'text-white'}">${device.alias}</h3>
                <p class="text-xs text-slate-500 mb-6 font-medium">ID: ${device.identifier}</p>

                ${isPending ? `
                    <div class="flex-1 flex flex-col items-center justify-center py-4 gap-3 text-center mb-6">
                        <span class="material-symbols-outlined text-3xl text-slate-600">pending</span>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Abre la app móvil e ingresa el código<br>
                            <span class="font-mono text-amber-400 font-bold">${device.identifier}</span><br>
                            para activar el dispositivo.
                        </p>
                    </div>
                ` : `
                    <div class="space-y-4 mb-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-slate-400">
                                <span class="material-symbols-outlined text-lg">${isCharging ? 'battery_charging_full' : 'battery_horiz_075'}</span>
                                <span class="text-sm font-bold">Battery</span>
                            </div>
                            <span class="text-sm font-black text-white">${batteryLevel ?? '--'}%</span>
                        </div>
                        <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div class="h-full ${(batteryLevel ?? 100) < 20 ? 'bg-red-500' : 'bg-[#6CD400]'}" style="width: ${batteryLevel ?? 0}%"></div>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Network</span>
                            <span class="text-[10px] font-mono text-[#005d70] bg-[#005d70]/10 px-2 py-0.5 rounded">${connectionType.toUpperCase()}</span>
                        </div>

                        ${device.signal_strength !== null && device.signal_strength !== undefined ? `
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Signal</span>
                            <div class="flex items-end gap-0.5">
                                ${Array.from({length: 4}, (_, i) => `<span class="w-1.5 rounded-sm ${i < device.signal_strength ? 'bg-[#6CD400]' : 'bg-slate-700'}" style="height: ${8 + i * 5}px"></span>`).join('')}
                            </div>
                        </div>` : ''}

                        ${device.has_internet !== null && device.has_internet !== undefined ? `
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Internet</span>
                            <span class="text-[10px] font-mono ${device.has_internet ? 'text-emerald-400' : 'text-red-400'} px-2 py-0.5 rounded ${device.has_internet ? 'bg-emerald-500/10' : 'bg-red-500/10'}">
                                ${device.has_internet ? 'ONLINE' : 'OFFLINE'}
                            </span>
                        </div>` : ''}

                        ${device.tracking_state ? `
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Tracking</span>
                            <span class="text-[9px] font-mono ${device.tracking_state.includes('UNSAFE') ? 'text-amber-400 bg-amber-500/10' : 'text-emerald-400 bg-emerald-500/10'} px-2 py-0.5 rounded">
                                ${device.tracking_state.replace(/_/g, ' ')}
                            </span>
                        </div>` : ''}

                        ${device.activity_status ? `
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Estado</span>
                            <span class="text-[9px] font-mono text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded">
                                ${device.activity_status}
                            </span>
                        </div>` : ''}

                        ${device.speed_kmh !== null && device.speed_kmh !== undefined ? `
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Velocidad</span>
                            <span class="text-[9px] font-mono text-emerald-400">
                                ${parseFloat(device.speed_kmh).toFixed(1)} km/h
                            </span>
                        </div>` : ''}

                        ${device.intervalo_aplicado ? `
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Intervalo</span>
                            <span class="text-[9px] font-mono text-cyan-400">
                                Cada ${device.intervalo_aplicado}s
                            </span>
                        </div>` : ''}

                        ${device.motivo ? `
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Motivo</span>
                            <span class="text-[9px] font-mono text-purple-400">
                                ${device.motivo.replace(/_/g, ' ').toUpperCase()}
                            </span>
                        </div>` : ''}
                    </div>
                `}
                <div class="text-[10px] ${isPending ? 'text-amber-500/50' : 'text-slate-600'} font-mono mb-6 italic">
                    ${isPending ? 'ESPERANDO PRIMERA CONEXIÓN...' : 'LAST SYNC: ' + lastSeen}
                </div>

                <div class="mt-auto flex items-center gap-3">
                    ${!isPending
                        ? `<a href="${device.show_url}" class="btn-manage flex-1 bg-[#005d70] text-white text-center py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all hover:brightness-110">Manage Node</a>`
                        : `<span class="flex-1 bg-slate-800 text-slate-600 text-center py-3 rounded-xl text-xs font-bold uppercase tracking-wider cursor-not-allowed">Teléfono en Espera</span>`
                    }
                    <form action="${device.delete_url}" method="POST"
                          onsubmit="return confirm('¿Confirmas la desconexión total del nodo ${device.alias}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500/10 text-red-500 p-3 rounded-xl hover:bg-red-500 hover:text-white transition-all">
                            <span class="material-symbols-outlined text-xl">delete</span>
                        </button>
                    </form>
                </div>
            </div>`;
        }

        function renderEmptyState() {
            return `
            <div class="col-span-full bg-[#1c1e21] border border-dashed border-slate-800 rounded-3xl p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-slate-600 mb-4">cell_tower</span>
                <h3 class="text-lg font-bold text-white mb-2">No hay dispositivos vinculados</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-6">Vincula un teléfono para comenzar a recibir información de telemetría y ubicación en tiempo real.</p>
                <button onclick="openLinkModal()" class="bg-[#005d70] hover:bg-[#007b94] text-white px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                    Vincular Primer Teléfono
                </button>
            </div>`;
        }

        function updateDashboard(data) {
            if (!data.success) return;

            // Actualizar stats
            document.getElementById('stat-total').textContent = data.stats.total;
            document.getElementById('stat-moving').textContent = data.stats.moving;
            document.getElementById('stat-online').textContent = data.stats.online;
            document.getElementById('stat-alerts').textContent = data.stats.alerts;

            // Solo actualizar grilla si el modal no está abierto
            if (document.getElementById('link-modal').classList.contains('hidden')) {
                const grid = document.getElementById('devices-grid');
                if (data.devices.length === 0) {
                    grid.innerHTML = renderEmptyState();
                } else {
                    grid.innerHTML = data.devices.map(renderDeviceCard).join('');
                }
            }

            // Actualizar indicador de sync y latencia
            updateSyncIndicator('connected', `SSE en vivo · ${data.devices.length} disp.`);
            updateLatency(data.server_time);
        }

        function connectSSE() {
            updateSyncIndicator('connecting', 'Estableciendo canal SSE...');

            const eventSource = new EventSource('{{ route("dashboard.sse") }}');

            // Recibir actualizaciones completas del dashboard
            eventSource.addEventListener('update', function (e) {
                try {
                    const data = JSON.parse(e.data);
                    updateDashboard(data);
                } catch (err) {
                    console.warn('SSE parse error:', err);
                }
            });

            // Heartbeat — mantiene la conexión viva, actualiza latencia
            eventSource.addEventListener('heartbeat', function (e) {
                try {
                    const hb = JSON.parse(e.data);
                    updateLatency(hb.time);
                } catch (err) {
                    console.warn('Heartbeat parse error:', err);
                }
                syncTime.textContent = new Date().toLocaleTimeString();
            });

            // Reconexión automática — EventSource ya reconecta, solo actualizamos el indicador
            eventSource.onopen = function () {
                updateSyncIndicator('connected', 'SSE en vivo');
                reconnectAttempts = 0;
            };

            eventSource.onerror = function () {
                reconnectAttempts++;

                // Exponential backoff: EventSource reconecta automáticamente,
                // pero mostramos el estado al usuario
                const backoff = Math.min(1000 * Math.pow(2, reconnectAttempts), MAX_RECONNECT_BACKOFF);
                updateSyncIndicator('error', `Reconectando en ${Math.round(backoff / 1000)}s...`);

                // Si lleva mucho tiempo desconectado, mostrar mensaje claro
                if (reconnectAttempts >= 5) {
                    updateSyncIndicator('error', 'Servidor no disponible');
                }
            };

            return eventSource;
        }

        // Iniciar conexión SSE al cargar la página
        let sseConnection = null;
        document.addEventListener('DOMContentLoaded', function () {
            sseConnection = connectSSE();
        });
    </script>
</body>
</html>