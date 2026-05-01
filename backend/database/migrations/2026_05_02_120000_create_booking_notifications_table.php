<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('internal');
            $table->string('event');
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index('event');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_notifications');
    }
};
