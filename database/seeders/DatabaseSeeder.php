<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear un único usuario para pruebas
        $mainUser = User::factory()->create([
            'name' => 'Admin DevUbi',
            'email' => 'admin@devubi.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. Crear un dispositivo para este usuario
        $device = \App\Models\Device::create([
            'user_id' => $mainUser->id,
            'alias' => 'Mi iPhone 15',
            'identifier' => 'UUID-1234-5678-9012',
            'latitude' => 19.432608,
            'longitude' => -99.133209,
            'battery_level' => 85,
            'is_charging' => false,
            'connection_type' => 'wifi',
            'activity' => 'still',
            'screen_active' => true,
            'last_seen' => now(),
        ]);

        // 3. Crear lugares seguros
        \App\Models\SafePlace::create([
            'device_id' => $device->id,
            'name' => 'Casa',
            'latitude' => 19.432608,
            'longitude' => -99.133209,
            'radius_meters' => 100,
        ]);

        \App\Models\SafePlace::create([
            'device_id' => $device->id,
            'name' => 'Trabajo',
            'latitude' => 19.428470,
            'longitude' => -99.167660,
            'radius_meters' => 200,
        ]);

        // 4. Crear historial de ubicaciones simulando un recorrido
        $locations = [
            ['lat' => 19.432608, 'lng' => -99.133209],
            ['lat' => 19.431000, 'lng' => -99.140000],
            ['lat' => 19.430000, 'lng' => -99.150000],
            ['lat' => 19.428470, 'lng' => -99.167660],
        ];

        foreach ($locations as $index => $loc) {
            $lh = \App\Models\LocationHistory::create([
                'device_id' => $device->id,
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
                'battery_level' => 85 - $index,
                'is_charging' => false,
                'connection_type' => 'mobile',
                'activity' => 'in_vehicle',
                'screen_active' => false,
                'created_at' => now()->subMinutes(30 - ($index * 10)),
            ]);

            // Actualizar el estado del dispositivo con la última ubicación del historial
            if ($index === count($locations) - 1) {
                $device->update([
                    'latitude' => $lh->latitude,
                    'longitude' => $lh->longitude,
                    'battery_level' => $lh->battery_level,
                    'is_charging' => $lh->is_charging,
                    'connection_type' => $lh->connection_type,
                    'activity' => $lh->activity,
                    'screen_active' => $lh->screen_active,
                    'last_seen' => $lh->created_at,
                ]);
            }
        }
    }
}
