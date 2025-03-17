<?php

use App\Http\Controllers\Api\V1\{TransactionController, AuthController, UserController};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/transactions/deposit', [TransactionController::class, 'deposit']);
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('users', UserController::class);
    });
});
