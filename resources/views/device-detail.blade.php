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
                    <p class="text-[#8dc3ce] text-[10px] font-bold tracking-[0.3em] uppercase">Real-time Telemetry</p>
                </div>
            </div>
            <div class="bg-primary/10 border border-primary/20 px-4 py-2 rounded-full">
                <span class="text-[10px] font-mono text-emerald-500 animate-pulse">● ENCRYPTED LINK ACTIVE</span>
            </div>
        </header>
    </div>

    <div class="flex-1 px-6 pb-6 min-h-0">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-full">
            
            <div class="lg:col-span-3 flex flex-col gap-4 h-full overflow-y-auto pr-2">
                <div class="bg-[#1c1e21] p-6 rounded-2xl border border-slate-800">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-emerald-500/10 p-2 rounded-lg text-emerald-500">
                            <span class="material-symbols-outlined">api</span>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-500 uppercase font-bold">Status</p>
                            <p class="text-white font-bold tracking-wider">{{ strtoupper($device->activity) }}</p>
                        </div>
                    </div>
                    
                    <h2 class="text-4xl font-black text-white mb-1">{{ $device->battery_level }}<span class="text-lg text-slate-500">%</span></h2>
                    <p class="text-[10px] text-slate-400 mb-4">Estimated: 14h 22m remaining</p>
                    <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden mb-6">
                        <div class="h-full {{ $device->battery_level > 20 ? 'bg-emerald-500' : 'bg-red-500' }}" style="width: {{ $device->battery_level }}%"></div>
                    </div>

                    <div class="bg-slate-800/50 p-4 rounded-xl border border-white/5">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-[10px] text-slate-400 uppercase">Network Type</span>
                            <span class="bg-blue-500/20 text-blue-400 text-[9px] px-1.5 py-0.5 rounded font-bold">{{ strtoupper($device->connection_type ?? 'N/A') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-500">signal_cellular_alt</span>
                            <span class="text-white font-mono text-sm">-84 dBm</span>
                        </div>
                    </div>
                </div>

                <button class="mt-auto bg-red-500/10 border border-red-500/50 text-red-500 w-full py-4 rounded-xl font-bold text-xs uppercase hover:bg-red-500 hover:text-white transition-all flex items-center justify-center gap-2 shrink-0">
                    <span class="material-symbols-outlined text-sm">lock</span> Emergency Lock
                </button>
            </div>

            <div class="lg:col-span-6 h-full relative">
                <div class="absolute inset-0 bg-black rounded-3xl border border-slate-800 overflow-hidden shadow-2xl">
                    <div class="absolute top-4 left-4 right-4 z-[400]">
                        <div class="bg-[#1c1e21]/90 backdrop-blur-md border border-slate-700 text-slate-300 rounded-xl p-3 flex items-center gap-3 shadow-lg">
                            <span class="material-symbols-outlined text-slate-500">search</span>
                            <input type="text" placeholder="Search coordinates or address..." class="bg-transparent w-full outline-none text-sm placeholder:text-slate-600">
                            <span class="material-symbols-outlined text-slate-500 cursor-pointer hover:text-white">near_me</span>
                        </div>
                    </div>
                    <div id="map"></div>
                </div>
            </div>

            <div class="lg:col-span-3 flex flex-col gap-4 h-full overflow-y-auto pl-2">
                <div class="bg-[#1c1e21] p-5 rounded-2xl border border-slate-800 shrink-0">
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Technical Metrics</h4>
                    <div class="bg-black/40 p-4 rounded-xl border border-white/5 relative group">
                        <button onclick="copyToClipboard('{{ $device->latitude }}, {{ $device->longitude }}')" class="absolute right-3 top-3 text-slate-600 hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-sm">content_copy</span>
                        </button>
                        <p class="text-[10px] text-slate-500 mb-1">Current Coordinates</p>
                        <div class="font-mono text-sm text-white">
                            <p>LAT: {{ number_format($device->latitude, 6) }}°</p>
                            <p>LNG: {{ number_format($device->longitude, 6) }}°</p>
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-white/5 pt-2">
                            <span class="text-[10px] text-slate-500">ALT: 42m</span>
                            <span class="text-[9px] text-emerald-500 font-bold flex items-center gap-1">
                                <span class="size-1 bg-emerald-500 rounded-full"></span> PRECISION OK
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-[#1c1e21] p-5 rounded-2xl border border-slate-800 flex-1 min-h-[200px]">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ping History</h4>
                        <span class="text-[9px] text-primary font-bold cursor-pointer hover:text-white">LIVE</span>
                    </div>
                    <div class="space-y-3">
                        @foreach($device->locationHistories->sortByDesc('created_at')->take(4) as $history)
                        <div class="p-3 rounded-xl border border-white/5 {{ $loop->first ? 'bg-slate-800/60 border-l-2 border-l-emerald-500' : 'bg-transparent opacity-60' }}">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-white font-bold text-xs">{{ $history->created_at->diffForHumans(null, true, true) }} ago</span>
                                @if($loop->first) <span class="material-symbols-outlined text-[10px] text-emerald-500">check_circle</span> @endif
                            </div>
                            <p class="text-[10px] text-slate-400 italic">
                                {{ $history->activity == 'moving' ? 'Movement detected' : 'Stationary report' }}
                            </p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-[#1c1e21] p-5 rounded-2xl border border-slate-800 space-y-3 shrink-0">
                    <button class="w-full flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 hover:bg-slate-700 transition-all border border-slate-700 text-left group">
                        <div class="bg-slate-700 group-hover:bg-slate-600 p-1.5 rounded-lg text-white transition-colors">
                            <span class="material-symbols-outlined text-sm">edit_square</span>
                        </div>
                        <span class="text-xs font-bold text-slate-300">Create Perimeter</span>
                    </button>
                    <button class="w-full flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 hover:bg-slate-700 transition-all border border-slate-700 text-left group">
                        <div class="bg-slate-700 group-hover:bg-slate-600 p-1.5 rounded-lg text-white transition-colors">
                            <span class="material-symbols-outlined text-sm">notifications_active</span>
                        </div>
                        <span class="text-xs font-bold text-slate-300">Alert Radius</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // 1. Definir coordenadas base
        const lat = {{ $device->latitude }};
        const lng = {{ $device->longitude }};

        // 2. Inicializar Mapa
        var map = L.map('map', {
            zoomControl: false,
            attributionControl: false
        }).setView([lat, lng], 16);

        // 3. Capa de Mapa (Midnight Blue)
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 16
        }).addTo(map);

        // 4. Definir puntos del historial (¡IMPORTANTE: Antes de crear la polilínea!)
        var pathPoints = [
            @foreach ($device->locationHistories as $point)
                [{{ $point->latitude }}, {{ $point->longitude }}],
            @endforeach
        ];

        // 5. Dibujar Ruta (Cian Neón de Alto Contraste)
        if (pathPoints.length > 0) {
            var polyline = L.polyline(pathPoints, {
                color: '#00e5ff', 
                weight: 5, 
                opacity: 0.8,
                lineJoin: 'round',
                dashArray: '1, 10', // Efecto punteado tecnológico
                dashOffset: '10'
            }).addTo(map);
            
            // Auto-ajustar vista
            map.fitBounds(polyline.getBounds(), { padding: [50, 50] });
        }

        // 6. Icono Personalizado (Pulsante)
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

        // 7. Colocar Marcador Actual
        L.marker([lat, lng], { icon: unitIcon })
            .addTo(map)
            .bindPopup('<b class="text-slate-900">{{ $device->alias }}</b>');

        // Función auxiliar para copiar
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert('Coordinates copied to clipboard');
        }
    </script>
</body>
</html>