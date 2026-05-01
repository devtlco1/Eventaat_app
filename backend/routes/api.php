<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BookingController;
use App\Http\Controllers\Api\Mobile\BookingReviewController;
use App\Http\Controllers\Api\Mobile\MeController;
use App\Http\Controllers\Api\Mobile\MyReviewsController;
use App\Http\Controllers\Api\Mobile\RestaurantDiscoveryController;
use App\Http\Controllers\Api\Mobile\RestaurantReviewController;
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

        Route::get('restaurants', [RestaurantDiscoveryController::class, 'index'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
        Route::get('restaurants/{restaurant:slug}/reviews', [RestaurantReviewController::class, 'index'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
        Route::get('restaurants/{restaurant:slug}', [RestaurantDiscoveryController::class, 'show'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);

        Route::get('me/reviews', [MyReviewsController::class, 'index'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
        Route::get('me/reviews/{review}', [MyReviewsController::class, 'show'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);

        Route::get('bookings', [BookingController::class, 'index'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
        Route::post('bookings', [BookingController::class, 'store'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
        Route::get('bookings/{booking}', [BookingController::class, 'show'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
        Route::post('bookings/{booking}/review', [BookingReviewController::class, 'store'])
            ->middleware(['auth:sanctum', 'mobile.token', 'mobile.customer']);
    });
