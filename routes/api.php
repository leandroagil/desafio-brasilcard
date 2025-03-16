<?php

use App\Http\Controllers\Api\V1\{TransactionController, UserController};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('transactions', TransactionController::class);
    Route::post('/transactions/deposit', [TransactionController::class, 'deposit']);

    Route::apiResource('users', UserController::class);
});
