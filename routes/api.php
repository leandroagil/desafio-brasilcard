<?php

use App\Http\Controllers\Api\V1\{TransactionController, AuthController};
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::post('/transactions/deposit', [TransactionController::class, 'deposit'])->middleware('auth:sanctum');

    Route::apiResource('transactions', TransactionController::class)->middleware('auth:sanctum');
    Route::post('/transactions/deposit', [TransactionController::class, 'deposit'])->middleware('auth:sanctum');

    Route::apiResource('users', AuthController::class)->middleware('auth:sanctum');
});
