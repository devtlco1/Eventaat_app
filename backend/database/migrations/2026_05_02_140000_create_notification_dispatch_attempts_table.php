<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_dispatch_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_notification_id')
                ->constrained('booking_notifications')
                ->cascadeOnDelete();
            $table->string('provider');
            $table->string('channel');
            $table->string('status');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index('booking_notification_id');
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_dispatch_attempts');
    }
};

