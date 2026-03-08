<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::get('/wallet', [WalletController::class, 'balance']);
    Route::post('/topup', [WalletController::class, 'topup']);
    Route::post('/transfer', [WalletController::class, 'transfer']);
    Route::get('/transactions', [WalletController::class, 'transactions']);
    Route::get('/recent-transfers', [WalletController::class, 'recentTransfers']);
});
