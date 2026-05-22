<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\HandshakeController;
use App\Http\Controllers\Api\V1\TelemetryController;
use App\Http\Controllers\Api\V1\DeviceApiController;
use App\Http\Controllers\Api\V1\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes — Warey Mobile
|--------------------------------------------------------------------------
|
| Versión 1 (v1) de la API.
|
| Rutas públicas (sin autenticación):
|   POST /api/v1/auth/login
|
| Rutas protegidas (requieren Bearer Token de Sanctum):
|   POST   /api/v1/auth/logout
|   GET    /api/v1/auth/me
|   POST   /api/v1/handshake
|   POST   /api/v1/telemetry
|   GET    /api/v1/telemetry/{identifier}/history
|   GET    /api/v1/devices
|   GET    /api/v1/devices/{identifier}
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
            Route::get('/me',     [AuthApiController::class, 'me']);
        });

        // Telemetría — envío de datos GPS+sensores e historial
        Route::prefix('telemetry')->group(function () {
            Route::post('/',                             [TelemetryController::class, 'update']);
            Route::get('/{identifier}/history',          [TelemetryController::class, 'history']);
        });

        // Ubicación GPS — frames de posición desde el motor de rastreo de la app móvil
        // La app (warey_movil) usa LocationRepository → POST /api/v1/location
        Route::post('location', [LocationController::class, 'store']);

        // Dispositivos — listado y detalle
        Route::prefix('devices')->group(function () {
            Route::get('/',              [DeviceApiController::class, 'index']);
            Route::get('/{identifier}', [DeviceApiController::class, 'show']);
        });

    });
});