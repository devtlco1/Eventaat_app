<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP outbound driver (mobile auth)
    |--------------------------------------------------------------------------
    |
    | Local/dev default: log — OTP is written to the app log only (no SMS).
    |
    | Production SMS: OTP_DRIVER=twilio_sms — Messaging Service SID + REST API.
    |
    | Production WhatsApp: OTP_DRIVER=twilio_whatsapp — approved Twilio Content
    | Template (Authentication); see otp.twilio.whatsapp below.
    |
    | Reserved (fail fast): sms, whatsapp — use twilio_sms / twilio_whatsapp.
    |
    | Env: OTP_DRIVER=log
    |
    */
    'otp' => [
        'driver' => env('OTP_DRIVER', 'log'),
        'twilio' => [
            'otp_validity_period' => (int) env('TWILIO_OTP_VALIDITY_PERIOD', 300),
            'whatsapp' => [
                'from' => env('TWILIO_WHATSAPP_FROM', ''),
                'otp_content_sid' => env('TWILIO_WHATSAPP_OTP_CONTENT_SID', ''),
                'otp_validity_period' => (int) env('TWILIO_OTP_VALIDITY_PERIOD', 300),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio shared credentials + SMS / WhatsApp OTP settings
    |--------------------------------------------------------------------------
    |
    | When OTP_DRIVER=log, unused values may be unset.
    |
    | WhatsApp OTP reads sender + Content SID from otp.twilio.whatsapp.* above.
    |
    */
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
        'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID', ''),
        'otp_validity_period' => (int) env('TWILIO_OTP_VALIDITY_PERIOD', 300),
        'whatsapp' => [
            'from' => env('TWILIO_WHATSAPP_FROM', ''),
            'otp_content_sid' => env('TWILIO_WHATSAPP_OTP_CONTENT_SID', ''),
            'otp_validity_period' => (int) env('TWILIO_OTP_VALIDITY_PERIOD', 300),
        ],
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
