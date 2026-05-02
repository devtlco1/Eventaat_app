<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP outbound driver (mobile auth)
    |--------------------------------------------------------------------------
    |
    | Local/dev default: log — OTP is written to the app log only (no SMS).
    |
    | Planned (not shipped yet): sms, whatsapp — configuring these fails fast until
    | a real integration exists.
    |
    | Env: OTP_DRIVER=log
    |
    */
    'otp' => [
        'driver' => env('OTP_DRIVER', 'log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking notification outbound driver (dashboard/internal pipeline)
    |--------------------------------------------------------------------------
    |
    | Default: dry_run — records dispatch attempts without external calls.
    |
    | Planned (not shipped yet): sms, whatsapp — configuring these fails fast until
    | a real integration exists.
    |
    | Env: NOTIFICATION_DRIVER=dry_run
    |
    */
    'booking_notifications' => [
        'driver' => env('NOTIFICATION_DRIVER', 'dry_run'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking arrival reminder window
    |--------------------------------------------------------------------------
    |
    | Accepted bookings with starts_at in (now, now + N hours] get one internal
    | booking_arrival_reminder row via php artisan eventaat:booking-reminders.
    | Uses app timezone (see starts_at comparisons via Carbon / DB).
    |
    | Env: BOOKING_REMINDER_HOURS=2
    |
    */
    'booking_reminder_hours' => (float) env('BOOKING_REMINDER_HOURS', 2),

];
