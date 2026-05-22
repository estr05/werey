<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\LocationHistory;
use Illuminate\Console\Command;

class DeviceDiagnose extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:diagnose {id : ID del dispositivo a diagnosticar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra un reporte completo de diagnóstico de un dispositivo';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deviceId = (int) $this->argument('id');

        $device = Device::with(['user', 'safePlaces'])->find($deviceId);

        if (! $device) {
            $this->components->error("Dispositivo con ID {$deviceId} no encontrado.");
            return Command::FAILURE;
        }

        // ─────────────────────────────────────────────────────────────────────────
        //  1. INFORMACIÓN BÁSICA
        // ─────────────────────────────────────────────────────────────────────────
        $this->components->twoColumnDetail('<fg=cyan>Dispositivo</>', "#{$device->id}");

        $this->newLine();
        $this->components->twoColumnDetail('Alias', $device->alias ?? '<fg=gray>—</>');
        $this->components->twoColumnDetail('Identifier (WRY)', "<options=bold>{$device->identifier}</>");
        $this->components->twoColumnDetail('Usuario propietario', $device->user->name . ' <fg=gray>#' . $device->user->id . '</>');
        $this->components->twoColumnDetail('Email', $device->user->email);
        $this->components->twoColumnDetail('Creado', $device->created_at?->format('d/m/Y H:i:s') . ' <fg=gray>(' . $device->created_at?->diffForHumans() . ')</>');

        // ─────────────────────────────────────────────────────────────────────────
        //  2. ESTADO DEL DISPOSITIVO
        // ─────────────────────────────────────────────────────────────────────────
        $this->newLine();
        $this->components->twoColumnDetail('<fg=yellow>Estado</>', '');

        $now = now();

        // Online / Offline
        $isOnline = $device->last_seen && $device->last_seen->gte($now->copy()->subMinutes(5));
        $onlineBadge = $isOnline
            ? '<fg=green;options=bold>● ONLINE</>'
            : '<fg=red;options=bold>● OFFLINE</>';
        $this->components->twoColumnDetail('  Estado conexión', $onlineBadge);

        // Battery
        $batteryStr = '';
        if ($device->battery_level !== null) {
            $batteryEmoji = $device->battery_level > 20 ? '<fg=green>' : '<fg=red>';
            $batteryStr = $batteryEmoji . $device->battery_level . '%</>';
            if ($device->is_charging) {
                $batteryStr .= ' <fg=yellow>⚡ CARGANDO</>';
            }
        } else {
            $batteryStr = '<fg=gray>—</>';
        }
        $this->components->twoColumnDetail('  Batería', $batteryStr);

        // Connection type
        $connType = $device->connection_type
            ? ($device->connection_type === 'wifi' ? '<fg=cyan>WiFi</>' : '<fg=magenta>Cellular</>')
            : '<fg=gray>—</>';
        $this->components->twoColumnDetail('  Conexión', $connType);

        // Screen
        $screenStr = $device->screen_active !== null
            ? ($device->screen_active ? '<fg=green>Activa</>' : '<fg=gray>Inactiva</>')
            : '<fg=gray>—</>';
        $this->components->twoColumnDetail('  Pantalla', $screenStr);

        // Activity
        $this->components->twoColumnDetail('  Actividad', $device->activity ?? '<fg=gray>—</>');

        // Tracking
        $this->components->twoColumnDetail('  Tracking', $device->tracking_state ?? '<fg=gray>—</>');

        // Signal / Internet
        $signalStr = $device->signal_strength !== null
            ? $device->signal_strength . '%'
            : '<fg=gray>—</>';
        $this->components->twoColumnDetail('  Señal', $signalStr);
        $this->components->twoColumnDetail('  Internet', $device->has_internet ? '<fg=green>Sí</>' : ($device->has_internet === false ? '<fg=red>No</>' : '<fg=gray>—</>'));

        // ─────────────────────────────────────────────────────────────────────────
        //  3. UBICACIÓN ACTUAL
        // ─────────────────────────────────────────────────────────────────────────
        $this->newLine();
        $this->components->twoColumnDetail('<fg=blue>Ubicación</>', '');
        $hasLocation = $device->latitude && $device->longitude;
        if ($hasLocation) {
            $this->components->twoColumnDetail('  Latitud', number_format($device->latitude, 6));
            $this->components->twoColumnDetail('  Longitud', number_format($device->longitude, 6));
            $mapsUrl = "https://www.google.com/maps?q={$device->latitude},{$device->longitude}";
            $this->components->twoColumnDetail('  Maps', "<fg=blue>{$mapsUrl}</>");
        } else {
            $this->components->twoColumnDetail('  Lat/Lng', '<fg=gray>Sin datos de ubicación</>');
        }

        // ─────────────────────────────────────────────────────────────────────────
        //  4. INFORMACIÓN TEMPORAL
        // ─────────────────────────────────────────────────────────────────────────
        $this->newLine();
        $this->components->twoColumnDetail('<fg=magenta>Tiempos</>', '');

        $this->components->twoColumnDetail('  last_seen', $device->last_seen?->format('d/m/Y H:i:s') ?? '<fg=gray>NUNCA</>');
        $this->components->twoColumnDetail('  Hace', $device->last_seen?->diffForHumans() ?? '<fg=gray>—</>');
        $this->components->twoColumnDetail('  last_status_at', $device->last_status_at?->format('d/m/Y H:i:s') ?? '<fg=gray>NUNCA</>');

        // ─────────────────────────────────────────────────────────────────────────
        //  5. ÚLTIMO FRAME DE LOCATION_HISTORY
        // ─────────────────────────────────────────────────────────────────────────
        $lastLoc = $device->locationHistories()
            ->latest()
            ->first();

        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Último Location Frame</>', '');
        if ($lastLoc) {
            $this->components->twoColumnDetail('  ID', "#{$lastLoc->id}");
            $this->components->twoColumnDetail('  Lat/Lng', number_format($lastLoc->latitude, 6) . ', ' . number_format($lastLoc->longitude, 6));
            $this->components->twoColumnDetail('  Batería', ($lastLoc->battery_level !== null ? $lastLoc->battery_level . '%' : '<fg=gray>—</>') . ($lastLoc->is_charging ? ' <fg=yellow>⚡</>' : ''));
            $this->components->twoColumnDetail('  Conexión', $lastLoc->connection_type ?? '<fg=gray>—</>');
            $this->components->twoColumnDetail('  Movimiento', $lastLoc->movement_type ?? '<fg=gray>—</>');
            $this->components->twoColumnDetail('  Pantalla', $lastLoc->screen_active !== null ? ($lastLoc->screen_active ? '<fg=green>Activa</>' : '<fg=gray>Inactiva</>') : '<fg=gray>—</>');
            $this->components->twoColumnDetail('  Creado', $lastLoc->created_at?->format('d/m/Y H:i:s') . ' <fg=gray>(' . $lastLoc->created_at?->diffForHumans() . ')</>');
        } else {
            $this->components->twoColumnDetail('  —', '<fg=gray>No hay registros de ubicación</>');
        }

        // ─────────────────────────────────────────────────────────────────────────
        //  6. ESTADÍSTICAS
        // ─────────────────────────────────────────────────────────────────────────
        $totalHistory = $device->locationHistories()->count();
        $safePlacesCount = $device->safePlaces->count();

        $this->newLine();
        $this->components->twoColumnDetail('<fg=cyan>Estadísticas</>', '');
        $this->components->twoColumnDetail('  Total location_histories', (string) $totalHistory);
        $this->components->twoColumnDetail('  Lugares seguros', (string) $safePlacesCount);

        // ─────────────────────────────────────────────────────────────────────────
        //  7. ÚLTIMOS 5 LOCATION HISTORIES (tabla)
        // ─────────────────────────────────────────────────────────────────────────
        $recent = $device->locationHistories()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        if ($recent->isNotEmpty()) {
            $this->newLine();
            $this->components->twoColumnDetail('<fg=cyan>Últimos 5 registros</>', '');

            $rows = $recent->map(fn ($h) => [
                '#' . $h->id,
                number_format($h->latitude, 4) . ', ' . number_format($h->longitude, 4),
                ($h->battery_level !== null ? $h->battery_level . '%' : '—') . ($h->is_charging ? ' ⚡' : ''),
                $h->movement_type ?? '—',
                $h->connection_type ?? '—',
                $h->screen_active ? 'Activa' : 'Inactiva',
                $h->created_at?->format('H:i:s d/m') ?? '—',
            ])->toArray();

            $this->table(
                ['ID', 'Lat/Lng', 'Batería', 'Movimiento', 'Red', 'Pantalla', 'Creado'],
                $rows
            );
        }

        // ─────────────────────────────────────────────────────────────────────────
        //  8. RESUMEN / ADVERTENCIAS
        // ─────────────────────────────────────────────────────────────────────────
        $this->newLine();
        $warnings = [];

        if ($device->last_seen === null) {
            $warnings[] = '<fg=red>⚠ Nunca se ha conectado. El dispositivo no ha hecho handshake.</>';
        } elseif (! $isOnline) {
            $warnings[] = '<fg=yellow>⚠ Última conexión: ' . $device->last_seen->diffForHumans() . '. El dispositivo parece estar offline.</>';
        }

        if ($device->battery_level === null) {
            $warnings[] = '<fg=yellow>⚠ Sin datos de batería. Device-status no se ha recibido aún.</>';
        }

        if ($device->latitude === null) {
            $warnings[] = '<fg=yellow>⚠ Sin ubicación. Location frames no se han recibido aún.</>';
        }

        if ($totalHistory === 0) {
            $warnings[] = '<fg=yellow>⚠ Sin historial de ubicación. La app no ha enviado frames GPS.</>';
        }

        if ($safePlacesCount === 0) {
            $warnings[] = '<fg=gray>ℹ Sin lugares seguros configurados.</>';
        }

        if (empty($warnings)) {
            $this->components->success('Dispositivo funcionando correctamente. Todos los datos están presentes.');
        } else {
            $this->components->twoColumnDetail('<fg=yellow>Diagnóstico</>', '');
            foreach ($warnings as $warning) {
                $this->line("  {$warning}");
            }
        }

        return Command::SUCCESS;
    }
}
