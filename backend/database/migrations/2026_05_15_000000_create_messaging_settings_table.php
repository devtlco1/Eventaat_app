<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_settings', function (Blueprint $table) {
            $table->id();
            /*
            | Kill-switch: when false, effective OTP driver is forced to "log"
            | and effective notification driver is forced to "dry_run" regardless
            | of the otp_driver / notification_driver columns below.
            */
            $table->boolean('external_messaging_enabled')->default(false);
            /*
            | null  → fall back to OTP_DRIVER env / config default ("log")
            | log   → write OTP to Laravel log only (never sends SMS/WhatsApp)
            | twilio_sms       → Twilio Messaging Service (SMS)
            | twilio_whatsapp  → Twilio WhatsApp Authentication template
            |                    (only allowed when whatsapp_otp_enabled = true)
            */
            $table->string('otp_driver', 32)->nullable()->default(null);
            /*
            | null     → fall back to NOTIFICATION_DRIVER env / config default ("dry_run")
            | dry_run  → records attempt without any external call
            | twilio_sms → Twilio Messaging Service (SMS)
            */
            $table->string('notification_driver', 32)->nullable()->default(null);
            /*
            | Guard for WhatsApp OTP. Must be true before otp_driver = "twilio_whatsapp"
            | can be saved. Stays false by default until Meta/Twilio approve the
            | Authentication template for this account.
            */
            $table->boolean('whatsapp_otp_enabled')->default(false);
            /*
            | Optional note so admins can record why a driver was switched.
            */
            $table->text('notes')->nullable();
            /*
            | Who last changed these settings (platform user FK; nullable so the
            | seed row can be inserted without a real user ID).
            */
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_settings');
    }
};
