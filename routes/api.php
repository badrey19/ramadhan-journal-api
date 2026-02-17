<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\UserController;
use App\Models\Wallet;
use Illuminate\Support\Facades\Route;

// Grouping route yang butuh login
// Route Publik (Bisa diakses tanpa login)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Route Terproteksi (Harus pakai Bearer Token)
// Route Terproteksi (Harus pakai Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::post('/user/update', [UserController::class, 'update']);
    
    // Fitur Ramadan Tracker
    Route::prefix('activities')->group(function () {
        Route::get('/', [ActivityController::class, 'index']);
        Route::post('/', [ActivityController::class, 'store']);
        Route::patch('/{id}/toggle', [ActivityController::class, 'toggle']);
        Route::put('/{id}', [ActivityController::class, 'update']);
        Route::delete('/{id}', [ActivityController::class, 'destroy']);
    });

    // Fitur Finance (Keuangan)
    Route::prefix('finance')->group(function () {
        Route::get('/summary', [FinanceController::class, 'getSummary']); // Menampilkan total & budget
        Route::post('/salary', [FinanceController::class, 'addSalary']);  // Input gaji & alokasi
        Route::post('/expense', [FinanceController::class, 'addExpense']); // Input pengeluaran
    });
});