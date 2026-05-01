<?php

namespace App\Providers;

use App\Http\Middleware\EnsureMobileCustomer;
use App\Http\Middleware\EnsureValidSanctumToken;
use App\Http\Responses\FilamentLogoutResponse;
use App\Services\Otp\LocalLogOtpSender;
use App\Services\Otp\OtpSender;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as FilamentLogoutResponseContract;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpSender::class, LocalLogOtpSender::class);
        $this->app->bind(FilamentLogoutResponseContract::class, FilamentLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::aliasMiddleware('mobile.customer', EnsureMobileCustomer::class);
        Route::aliasMiddleware('mobile.token', EnsureValidSanctumToken::class);
    }
}
