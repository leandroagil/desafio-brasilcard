<?php

use App\Http\Controllers\Api\V1\{TransactionController, AuthController, UserController};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/transactions/deposit', [TransactionController::class, 'deposit']);
        Route::post('/transactions/reverse/{transaction}', [TransactionController::class, 'reverse']);

        Route::apiResource('transactions', TransactionController::class, ['except' => ['update']]);
        Route::apiResource('users', UserController::class, ['except' => ['update', 'store']]);
    });
});
