<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Auth\AuthController;

// --- RUTAS DE AUTENTICACIÓN (PÚBLICAS) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- RUTAS PROTEGIDAS (ACCESO EXCLUSIVO PARA USUARIOS REGISTRADOS) ---
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Endpoint JSON ligero para auto-refresh en tiempo real del dashboard
    Route::get('/dashboard/json', [DashboardController::class, 'jsonDevices'])->name('dashboard.json');
    
    // Endpoint SSE para actualizaciones en tiempo real (reemplaza el polling)
    Route::get('/dashboard/sse', [DashboardController::class, 'sseStream'])->name('dashboard.sse');
    
    Route::get('/device/{id}', [DashboardController::class, 'show'])->name('device.show');
    Route::delete('/device/{id}', [DashboardController::class, 'destroy'])->name('device.destroy');
    
    // Crear nuevo teléfono vinculado (máx. 3 por usuario)
    Route::post('/device', [DashboardController::class, 'storeDevice'])->name('device.store');
    
    // Rutas para Puntos/Zonas Seguras
    Route::post('/device/{id}/safe-place', [DashboardController::class, 'storeSafePlace'])->name('safe-place.store');
    Route::delete('/safe-place/{id}', [DashboardController::class, 'destroySafePlace'])->name('safe-place.destroy');

    // T4 — Historial GPS paginado por fecha (JSON, autenticado via sesión web)
    Route::get('/device/{id}/history', [DashboardController::class, 'historyJson'])->name('device.history');

    // T7-backend — SSE de posición en tiempo real para la vista del dispositivo
    Route::get('/device/{id}/sse', [DashboardController::class, 'deviceSseStream'])->name('device.sse');
});