<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Services\Otp\LocalLogOtpSender;
use App\Services\Otp\OtpSender;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpSender::class, LocalLogOtpSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::aliasMiddleware('mobile.customer', \App\Http\Middleware\EnsureMobileCustomer::class);
        Route::aliasMiddleware('mobile.token', \App\Http\Middleware\EnsureValidSanctumToken::class);
    }
}
