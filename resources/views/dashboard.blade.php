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
        <header class="mb-10">
            <h1 class="text-4xl font-black tracking-tighter uppercase italic text-white">Warey</h1>
            <p class="text-[#8dc3ce] text-xs font-bold tracking-widest uppercase">Telemetry Control Node</p>
        </header>

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
            @foreach($devices as $device)
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
            @endforeach
        </div>
    </div>

    <script>
        setInterval(function() {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    // Actualizamos solo el grid y los contadores para mayor fluidez
                    document.querySelector('#devices-grid').innerHTML = doc.querySelector('#devices-grid').innerHTML;
                })
                .catch(err => console.warn('Sync lost:', err));
        }, 5000); 
    </script>
</body>
</html>