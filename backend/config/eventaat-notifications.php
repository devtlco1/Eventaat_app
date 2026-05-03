<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP outbound driver (mobile auth)
    |--------------------------------------------------------------------------
    |
    | Local/dev default: log — OTP is written to the app log only (no SMS).
    |
    | Production SMS: twilio_sms — requires TWILIO_* env vars (see twilio below).
    |
    | Reserved (fail fast): sms, whatsapp — use twilio_sms for SMS until other
    | providers exist.
    |
    | Env: OTP_DRIVER=log
    |
    */
    'otp' => [
        'driver' => env('OTP_DRIVER', 'log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio (OTP via SMS only when OTP_DRIVER=twilio_sms)
    |--------------------------------------------------------------------------
    |
    | When OTP_DRIVER=log, these values are ignored and may be unset.
    |
    */
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
        'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID', ''),
        'otp_validity_period' => (int) env('TWILIO_OTP_VALIDITY_PERIOD', 300),
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
