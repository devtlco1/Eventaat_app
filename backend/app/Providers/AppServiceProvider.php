<?php

namespace App\Providers;

use App\Filament\Support\FilamentActionSizing;
use App\Http\Middleware\EnsureMobileCustomer;
use App\Http\Middleware\EnsureValidSanctumToken;
use App\Http\Responses\FilamentLogoutResponse;
use App\Models\SupportTicket;
use App\Observers\SupportTicketObserver;
use App\Services\Notifications\Providers\NotificationProvider;
use App\Services\Otp\OtpSender;
use App\Support\EventaatNotifications\BookingNotificationProviderFactory;
use App\Support\EventaatNotifications\OtpSenderFactory;
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
        $this->app->bind(OtpSender::class, fn (): OtpSender => OtpSenderFactory::make());

        $this->app->bind(
            NotificationProvider::class,
            fn (): NotificationProvider => BookingNotificationProviderFactory::make(),
        );
        $this->app->bind(FilamentLogoutResponseContract::class, FilamentLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentActionSizing::configure();

        SupportTicket::observe(SupportTicketObserver::class);

        Route::aliasMiddleware('mobile.customer', EnsureMobileCustomer::class);
        Route::aliasMiddleware('mobile.token', EnsureValidSanctumToken::class);
    }
}
