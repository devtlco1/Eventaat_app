<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\MeController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')
    ->middleware('api')
    ->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('request-otp', [AuthController::class, 'requestOtp'])
                ->middleware('throttle:10,1');
            Route::post('verify-otp', [AuthController::class, 'verifyOtp'])
                ->middleware('throttle:10,1');
            Route::post('logout', [AuthController::class, 'logout'])
                ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
        });

        Route::get('me', [MeController::class, 'show'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);

        Route::patch('me', [MeController::class, 'update'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
    });

