<?php

use App\Models\Device;
use App\Models\User;
use App\Models\LocationHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->device = Device::factory()->create([
        'user_id' => $this->user->id,
        'identifier' => 'WRY-TEST-1234',
        'alias' => 'Test Phone',
        'battery_level' => 80,
        'is_charging' => false,
        'connection_type' => 'wifi',
        'tracking_state' => 'SAFE',
    ]);
});

test('location controller stores dynamic telemetry when provided in camelCase', function () {
    $token = $this->user->createToken("device_token:{$this->device->id}")->plainTextToken;

    $response = postJson('/api/v1/location', [
        'identifier' => 'WRY-TEST-1234',
        'latitude' => 19.4326,
        'longitude' => -99.1332,
        'accuracy' => 10.5,
        'speed' => 1.5, // 1.5 m/s
        'movementType' => 'WALKING',
        'speedKmh' => 5.4,
        'intervaloAplicado' => 5,
        'motivo' => 'walking',
    ], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Frame de ubicación recibido correctamente.'
        ]);

    // Check device was updated
    $this->device->refresh();
    expect($this->device->latitude)->toBe(19.4326)
        ->and($this->device->longitude)->toBe(-99.1332)
        ->and($this->device->activity)->toBe('walking')
        ->and($this->device->speed_kmh)->toBe(5.4)
        ->and($this->device->intervalo_aplicado)->toBe(5)
        ->and($this->device->motivo)->toBe('walking');

    // Check LocationHistory was created with same metadata
    $history = LocationHistory::where('device_id', $this->device->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->latitude)->toBe(19.4326)
        ->and($history->longitude)->toBe(-99.1332)
        ->and($history->speed_kmh)->toBe(5.4)
        ->and($history->intervalo_aplicado)->toBe(5)
        ->and($history->motivo)->toBe('walking');
});

test('location controller computes fallback telemetry when metadata is omitted', function () {
    $token = $this->user->createToken("device_token:{$this->device->id}")->plainTextToken;

    $response = postJson('/api/v1/location', [
        'identifier' => 'WRY-TEST-1234',
        'latitude' => 19.4326,
        'longitude' => -99.1332,
        'accuracy' => 10.5,
        'speed' => 25.0, // 25 m/s = 90 km/h (HIGH SPEED)
        'movementType' => 'VEHICLE',
    ], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(200);

    $this->device->refresh();
    expect($this->device->speed_kmh)->toBe(90.0) // 25.0 * 3.6
        ->and($this->device->intervalo_aplicado)->toBe(2) // >= 80 km/h is 2s
        ->and($this->device->motivo)->toBe('high_speed');

    $history = LocationHistory::where('device_id', $this->device->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->speed_kmh)->toBe(90.0)
        ->and($history->intervalo_aplicado)->toBe(2)
        ->and($history->motivo)->toBe('high_speed');
});

test('telemetry controller stores dynamic telemetry and device status', function () {
    $token = $this->user->createToken("device_token:{$this->device->id}")->plainTextToken;

    $response = postJson('/api/v1/telemetry', [
        'identifier' => 'WRY-TEST-1234',
        'batteryLevel' => 95,
        'isCharging' => true,
        'connectionType' => 'cellular',
        'signalStrength' => 3,
        'hasInternet' => true,
        'trackingState' => 'UNSAFE',
        'activityStatus' => 'WALKING',
        'capturedAt' => now()->toIso8601String(),
        // With GPS
        'latitude' => 19.4326,
        'longitude' => -99.1332,
        'speed' => 3.0, // 3 m/s = 10.8 km/h (RUNNING)
    ], [
        'Authorization' => "Bearer $token"
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Telemetría recibida correctamente.'
        ]);

    $this->device->refresh();
    expect($this->device->battery_level)->toBe(95)
        ->and($this->device->is_charging)->toBe(true)
        ->and($this->device->connection_type)->toBe('cellular')
        ->and($this->device->signal_strength)->toBe(3)
        ->and($this->device->has_internet)->toBe(true)
        ->and($this->device->tracking_state)->toBe('UNSAFE')
        ->and($this->device->activity_status)->toBe('WALKING')
        ->and($this->device->speed_kmh)->toBe(10.8) // 3.0 * 3.6
        ->and($this->device->intervalo_aplicado)->toBe(4) // 7 to 15 km/h is 4s
        ->and($this->device->motivo)->toBe('running');

    $history = LocationHistory::where('device_id', $this->device->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->battery_level)->toBe(95)
        ->and($history->is_charging)->toBe(true)
        ->and($history->speed_kmh)->toBe(10.8)
        ->and($history->intervalo_aplicado)->toBe(4)
        ->and($history->motivo)->toBe('running');
});

test('dashboard json api returns telemetry metadata', function () {
    $this->device->update([
        'speed_kmh' => 22.5,
        'intervalo_aplicado' => 3,
        'motivo' => 'vehicle',
        'last_seen' => now(),
    ]);

    actingAs($this->user);

    $response = getJson('/dashboard/json');

    $response->assertStatus(200)
        ->assertJsonPath('devices.0.speed_kmh', 22.5)
        ->assertJsonPath('devices.0.intervalo_aplicado', 3)
        ->assertJsonPath('devices.0.motivo', 'vehicle');
});
