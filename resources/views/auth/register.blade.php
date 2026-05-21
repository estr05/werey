<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warey | Registrar Nodo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <style>
        body { font-family: 'Epilogue', sans-serif; }
        .glass-card {
            background: rgba(28, 30, 33, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glow-cyan:focus {
            box-shadow: 0 0 15px rgba(0, 229, 255, 0.3);
            border-color: rgba(0, 229, 255, 0.5);
        }
    </style>
</head>
<body class="bg-[#0f1011] text-slate-100 min-h-screen flex items-center justify-center relative overflow-hidden">
    
    <!-- Efectos de Luces de Fondo para Premium Feel -->
    <div class="absolute -top-40 -left-40 size-96 bg-[#005d70]/20 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 size-96 bg-[#6CD400]/10 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-md p-6 relative z-10">
        
        <div class="text-center mb-8">
            <h1 class="text-5xl font-black tracking-tighter uppercase italic text-white mb-2">Warey</h1>
            <p class="text-[#8dc3ce] text-[10px] font-bold tracking-[0.3em] uppercase">Telemetry Control Node</p>
        </div>

        <div class="glass-card rounded-3xl p-8 shadow-2xl">
            <div class="flex items-center gap-3 mb-6">
                <span class="material-symbols-outlined text-[#6CD400] text-2xl">person_add</span>
                <h2 class="text-xl font-bold text-white">Crear Cuenta</h2>
            </div>

            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-xs rounded-xl p-4 mb-6">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Nombre Completo</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-3 text-slate-500 text-lg">person</span>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                               class="w-full bg-slate-900/60 border border-slate-800 rounded-xl py-2.5 pl-10 pr-4 text-sm text-white placeholder-slate-600 outline-none glow-cyan transition-all"
                               placeholder="Juan Pérez">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Correo Electrónico</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-3 text-slate-500 text-lg">mail</span>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                               class="w-full bg-slate-900/60 border border-slate-800 rounded-xl py-2.5 pl-10 pr-4 text-sm text-white placeholder-slate-600 outline-none glow-cyan transition-all"
                               placeholder="juan@example.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Contraseña</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-3 text-slate-500 text-lg">lock</span>
                        <input type="password" name="password" id="password" required
                               class="w-full bg-slate-900/60 border border-slate-800 rounded-xl py-2.5 pl-10 pr-4 text-sm text-white placeholder-slate-600 outline-none glow-cyan transition-all"
                               placeholder="••••••••">
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Confirmar Contraseña</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-3 text-slate-500 text-lg">enhanced_encryption</span>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="w-full bg-slate-900/60 border border-slate-800 rounded-xl py-2.5 pl-10 pr-4 text-sm text-white placeholder-slate-600 outline-none glow-cyan transition-all"
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex justify-end pt-1">
                    <a href="{{ route('login') }}" class="text-xs text-[#6CD400] hover:underline font-semibold">¿Ya tienes cuenta? Inicia sesión</a>
                </div>

                <button type="submit" 
                        class="w-full bg-[#005d70] hover:bg-[#007b94] text-white py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all hover:shadow-[0_0_20px_rgba(0,93,112,0.4)] mt-2">
                    Registrar y Entrar
                </button>
            </form>
        </div>

        <p class="text-center text-[10px] text-slate-600 mt-8 font-mono">
            SECURE ACCESS PORTAL v1.0.2
        </p>
    </div>

</body>
</html>
