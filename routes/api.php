<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\HandshakeController;
use App\Http\Controllers\Api\V1\TelemetryController;
use App\Http\Controllers\Api\V1\DeviceApiController;
use App\Http\Controllers\Api\V1\DeviceInfoController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\DeviceStatusController;
use App\Http\Controllers\Api\V1\DiagnosticsController;
use App\Http\Controllers\Api\V1\SafePlaceController;

/*

|--------------------------------------------------------------------------
| API Routes — Warey Mobile
|--------------------------------------------------------------------------
|
| Versión 1 (v1) de la API.
|
| Rutas públicas (sin autenticación):
|   POST /api/v1/auth/login
|   POST /api/v1/devices/handshake
|
| Rutas protegidas (requieren Bearer Token de Sanctum):
|   Auth:      POST /auth/logout | GET /auth/me
|   Telemetry: POST /telemetry | GET /telemetry/{identifier}/history
|   Device:    POST /device-status | POST /location
|   Devices:   GET /devices | GET /devices/{identifier}
|   Device Me: GET /device | GET /device/safe-places | POST /device/safe-places
|   SafePlaces: GET|POST /devices/{id}/safe-places | DELETE /safe-places/{id}
|   Diagnostics: GET /diagnostics/device/{id}
|
*/

Route::prefix('v1')->group(function () {

    // -----------------------------------------------------------------------
    // AUTH — Rutas públicas (sin token)
    // -----------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthApiController::class, 'login']);
    });
    
    // Handshake — vinculación de dispositivo (Pública, retorna el token)
    Route::post('devices/handshake', [HandshakeController::class, 'pair']);

    // -----------------------------------------------------------------------
    // Rutas protegidas por Sanctum (requieren: Authorization: Bearer <token>)
    // -----------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {

        // Auth — cierre de sesión y datos del usuario actual
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthApiController::class, 'logout']);
            Route::get('/me',      [AuthApiController::class, 'me']);
        });

        // Telemetría — envío de datos GPS+sensores e historial
        Route::prefix('telemetry')->group(function () {
            Route::post('',                              [TelemetryController::class, 'update']);
            Route::get('/{identifier}/history',          [TelemetryController::class, 'history']);
        });

        // Estado del dispositivo — batería, conectividad, señal, tracking_state
        Route::post('device-status', [DeviceStatusController::class, 'store']);
        Route::post('device-status/batch', [DeviceStatusController::class, 'storeBatch']);

        // Ubicación GPS — frames de posición desde el motor de rastreo de la app móvil
        Route::post('location', [LocationController::class, 'store']);
        Route::post('location/batch', [LocationController::class, 'storeBatch']);

        // Diagnóstico — estado RAW del dispositivo (para debug)
        Route::prefix('diagnostics')->group(function () {
            Route::get('/device/{id}', [DiagnosticsController::class, 'show']);
        });

        // Dispositivos — listado y detalle (usan session para web, device_token para móvil)
        Route::prefix('devices')->group(function () {
            Route::get('/',                  [DeviceApiController::class, 'index']);
            Route::get('/{identifier}',      [DeviceApiController::class, 'show']);

            // Safe Places — zonas seguras del dispositivo (identificado por identifier)
            Route::get('/{identifier}/safe-places',   [SafePlaceController::class, 'index']);
            Route::post('/{identifier}/safe-places',  [SafePlaceController::class, 'store']);
        });

        // Device Info — datos del dispositivo asociado al device_token actual
        // (NO requiere identifier, lo resuelve desde el nombre del token)
        Route::prefix('device')->group(function () {
            Route::get('/',                   [DeviceInfoController::class, 'show']);
            Route::get('/safe-places',        [DeviceInfoController::class, 'safePlaces']);
            Route::post('/safe-places',       [DeviceInfoController::class, 'storeSafePlace']);
        });

        // Safe Places — operaciones directas por ID (requiere autenticación)
        Route::delete('/safe-places/{id}', [SafePlaceController::class, 'destroy']);

    });
});
