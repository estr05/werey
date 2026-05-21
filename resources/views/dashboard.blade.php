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

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-10">
            <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Total Fleet</p>
                <p class="text-3xl font-black text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Moving Now</p>
                <p class="text-3xl font-black text-[#6CD400]">{{ $stats['moving'] }}</p>
            </div>
            <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Online</p>
                <p class="text-3xl font-black text-[#005d70]">{{ $stats['online'] }}</p>
            </div>
            <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Alerts</p>
                <p class="text-3xl font-black text-red-500">{{ $stats['alerts'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="devices-grid">
            @forelse($devices as $device)
            <div class="bg-[#1c1e21] rounded-2xl border border-slate-800 p-6 hover:border-[#005d70]/50 transition-all flex flex-col">
                <div class="flex justify-between items-start mb-6">
                    <div class="bg-slate-800 size-12 rounded-xl flex items-center justify-center text-[#005d70]">
                        <span class="material-symbols-outlined">
                            {{ $device->activity == 'moving' ? 'navigation' : 'smartphone' }}
                        </span>
                    </div>
                    <span class="{{ $device->activity == 'moving' ? 'bg-[#6CD400]/20 text-[#6CD400]' : 'bg-slate-800 text-slate-500' }} px-3 py-1 rounded-full text-[10px] font-black uppercase flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full {{ $device->activity == 'moving' ? 'bg-[#6CD400] animate-pulse' : 'bg-slate-500' }}"></span>
                        {{ $device->activity }}
                    </span>
                </div>

                <h3 class="font-extrabold text-xl mb-1 truncate text-white">{{ $device->alias }}</h3>
                <p class="text-xs text-slate-500 mb-6 font-medium">ID: {{ $device->identifier }}</p>

                <div class="space-y-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-slate-400">
                            <span class="material-symbols-outlined text-lg">
                                {{ $device->is_charging ? 'battery_charging_full' : 'battery_horiz_075' }}
                            </span>
                            <span class="text-sm font-bold">Battery</span>
                        </div>
                        <span class="text-sm font-black text-white">{{ $device->battery_level }}%</span>
                    </div>
                    <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                        <div class="h-full {{ $device->battery_level < 20 ? 'bg-red-500' : 'bg-[#6CD400]' }}" 
                             style="width: {{ $device->battery_level }}%"></div>
                    </div>
                    
                    <div class="flex justify-between items-center pt-2">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Network</span>
                        <span class="text-[10px] font-mono text-[#005d70] bg-[#005d70]/10 px-2 py-0.5 rounded">
                            {{ strtoupper($device->connection_type ?? 'Offline') }}
                        </span>
                    </div>
                </div>
                
                <div class="text-[10px] text-slate-600 font-mono mb-6 italic">
                    LAST SYNC: {{ $device->last_seen ? $device->last_seen->diffForHumans() : 'NEVER' }}
                </div>

                <div class="mt-auto flex items-center gap-3">
                    <a href="{{ route('device.show', $device->id) }}" 
                       class="btn-manage flex-1 bg-[#005d70] text-white text-center py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all hover:brightness-110">
                        Manage Node
                    </a>
                    
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
                ⚙️ <span class="font-bold text-white">Modo Desarrollo:</span> Ingresa el ID único que generará la app móvil Flutter de este teléfono para sincronizar el canal de telemetría y habilitar el rastreo.
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
                    <label for="identifier" class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Identificador Único (ID del Teléfono)</label>
                    <input type="text" name="identifier" id="identifier" required
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl py-3 px-4 text-sm text-white placeholder-slate-600 outline-none focus:border-[#00e5ff] transition-all font-mono"
                           placeholder="Ej. WRY-89A2-BC90">
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

    <script>
        function openLinkModal() {
            document.getElementById('link-modal').classList.remove('hidden');
        }
        function closeLinkModal() {
            document.getElementById('link-modal').classList.add('hidden');
        }

        // Intervalo de refresco en vivo
        setInterval(function() {
            // Solo recargar si el modal no está abierto para no interrumpir el formulario
            if (document.getElementById('link-modal').classList.contains('hidden')) {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newGrid = doc.querySelector('#devices-grid');
                        if (newGrid) {
                            document.querySelector('#devices-grid').innerHTML = newGrid.innerHTML;
                        }
                    })
                    .catch(err => console.warn('Sync lost:', err));
            }
        }, 5000); 
    </script>
</body>
</html>