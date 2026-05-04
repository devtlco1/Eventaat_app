<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Singleton model — only one row (id = 1) ever exists.
 *
 * OTP driver values:
 *   null             → fall back to OTP_DRIVER env / config default ("log")
 *   log              → write OTP to Laravel log only
 *   twilio_sms       → Twilio Messaging Service (SMS)
 *   twilio_whatsapp  → Twilio WhatsApp Authentication template
 *
 * Notification driver values:
 *   null        → fall back to NOTIFICATION_DRIVER env / config default ("dry_run")
 *   dry_run     → records attempt without any external call
 *   twilio_sms  → Twilio Messaging Service (SMS)
 */
class MessagingSettings extends Model
{
    public const SINGLETON_ID = 1;

    // OTP driver constants
    public const OTP_DRIVER_LOG = 'log';

    public const OTP_DRIVER_TWILIO_SMS = 'twilio_sms';

    public const OTP_DRIVER_TWILIO_WHATSAPP = 'twilio_whatsapp';

    // Notification driver constants
    public const NOTIFICATION_DRIVER_DRY_RUN = 'dry_run';

    public const NOTIFICATION_DRIVER_TWILIO_SMS = 'twilio_sms';

    protected $fillable = [
        'external_messaging_enabled',
        'otp_driver',
        'notification_driver',
        'whatsapp_otp_enabled',
        'notes',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'external_messaging_enabled' => 'boolean',
            'whatsapp_otp_enabled' => 'boolean',
        ];
    }

    /**
     * Return the singleton row, or null if it has never been seeded.
     */
    public static function getInstance(): ?self
    {
        return self::find(self::SINGLETON_ID);
    }

    /**
     * Return the singleton row, creating it with safe defaults if absent.
     */
    public static function getOrCreate(): self
    {
        return self::firstOrCreate(
            ['id' => self::SINGLETON_ID],
            [
                'external_messaging_enabled' => false,
                'otp_driver' => null,
                'notification_driver' => null,
                'whatsapp_otp_enabled' => false,
            ]
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
