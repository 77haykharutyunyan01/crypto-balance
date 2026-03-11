<?php

use App\Http\Controllers\Api\CryptoBalanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('users/{user}')->group(function (): void {
    Route::post('deposit', [CryptoBalanceController::class, 'deposit']);
    Route::post('withdraw', [CryptoBalanceController::class, 'withdraw']);
});

Route::prefix('transactions/{transaction}')->group(function (): void {
    Route::post('confirm', [CryptoBalanceController::class, 'confirm']);
    Route::post('fail', [CryptoBalanceController::class, 'fail']);
});

