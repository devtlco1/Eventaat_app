<?php

namespace Tests\Feature;

use App\Exceptions\UnsupportedNotificationDriverException;
use App\Exceptions\UnsupportedOtpDriverException;
use App\Services\Notifications\Providers\InternalDryRunNotificationProvider;
use App\Services\Notifications\Providers\NotificationProvider;
use App\Services\Otp\LocalLogOtpSender;
use App\Services\Otp\OtpSender;
use App\Support\EventaatNotifications\BookingNotificationProviderFactory;
use App\Support\EventaatNotifications\OtpSenderFactory;
use Tests\TestCase;

class EventaatNotificationsConfigurationTest extends TestCase
{
    public function test_default_otp_driver_resolves_log_sender(): void
    {
        config(['eventaat-notifications.otp.driver' => 'log']);

        $sender = app(OtpSender::class);

        $this->assertInstanceOf(LocalLogOtpSender::class, $sender);
    }

    public function test_default_notification_driver_resolves_dry_run_provider(): void
    {
        config(['eventaat-notifications.booking_notifications.driver' => 'dry_run']);

        $provider = app(NotificationProvider::class);

        $this->assertInstanceOf(InternalDryRunNotificationProvider::class, $provider);
        $this->assertSame(InternalDryRunNotificationProvider::PROVIDER_NAME, $provider->identifier());
    }

    public function test_notification_driver_accepts_hyphenated_dry_run(): void
    {
        config(['eventaat-notifications.booking_notifications.driver' => 'dry-run']);

        $provider = app(NotificationProvider::class);

        $this->assertInstanceOf(InternalDryRunNotificationProvider::class, $provider);
    }

    public function test_unsupported_otp_driver_throws_clear_exception(): void
    {
        config(['eventaat-notifications.otp.driver' => 'sms']);

        $this->expectException(UnsupportedOtpDriverException::class);
        $this->expectExceptionMessage('sms');

        app(OtpSender::class);
    }

    public function test_unsupported_notification_driver_throws_clear_exception(): void
    {
        config(['eventaat-notifications.booking_notifications.driver' => 'whatsapp']);

        $this->expectException(UnsupportedNotificationDriverException::class);
        $this->expectExceptionMessage('whatsapp');

        app(NotificationProvider::class);
    }

    public function test_otp_sender_factory_matches_container_default(): void
    {
        config(['eventaat-notifications.otp.driver' => 'log']);

        $this->assertInstanceOf(LocalLogOtpSender::class, OtpSenderFactory::make());
        $this->assertInstanceOf(LocalLogOtpSender::class, OtpSenderFactory::make('log'));
    }

    public function test_booking_notification_provider_factory_rejects_unknown_driver(): void
    {
        $this->expectException(UnsupportedNotificationDriverException::class);

        BookingNotificationProviderFactory::make('carrier_pigeon');
    }
}
