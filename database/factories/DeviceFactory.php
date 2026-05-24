<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'alias' => fake()->word(),
            'identifier' => 'WRY-' . fake()->bothify('####-####'),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'battery_level' => fake()->numberBetween(0, 100),
            'is_charging' => fake()->boolean(),
            'connection_type' => fake()->randomElement(['wifi', 'cellular', 'none']),
            'activity' => fake()->randomElement(['still', 'walking', 'running', 'automotive', 'unknown']),
            'screen_active' => fake()->boolean(),
            'signal_strength' => fake()->numberBetween(0, 4),
            'has_internet' => fake()->boolean(),
            'tracking_state' => fake()->randomElement(['SAFE', 'UNSAFE']),
            'activity_status' => fake()->randomElement(['WALKING', 'STILL', 'IN_VEHICLE']),
        ];
    }
}
