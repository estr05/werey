<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Ruta predeterminada de Laravel para usuarios (puedes dejarla o borrarla)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- RUTA DE WAREY ---
// Esta es la ruta que recibirá los datos del GPS y sensores
Route::post('/update-location', [DeviceController::class, 'update']);

// Endpoint que usará Flutter para reportar datos
Route::post('/telemetry/update', [DeviceController::class, 'updateTelemetry']);

// Endpoint que usará el Dashboard Web para ver los dispositivos
Route::get('/devices', [DeviceController::class, 'index']);