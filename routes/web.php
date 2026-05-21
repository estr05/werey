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
    Route::get('/device/{id}', [DashboardController::class, 'show'])->name('device.show');
    Route::delete('/device/{id}', [DashboardController::class, 'destroy'])->name('device.destroy');
    
    // Crear nuevo teléfono vinculado (máx. 3 por usuario)
    Route::post('/device', [DashboardController::class, 'storeDevice'])->name('device.store');
    
    // Rutas para Puntos/Zonas Seguras
    Route::post('/device/{id}/safe-place', [DashboardController::class, 'storeSafePlace'])->name('safe-place.store');
    Route::delete('/safe-place/{id}', [DashboardController::class, 'destroySafePlace'])->name('safe-place.destroy');
});