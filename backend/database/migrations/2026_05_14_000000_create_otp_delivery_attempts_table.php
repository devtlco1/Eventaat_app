<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('phone_hash', 64)->index();
            $table->string('phone_masked', 48);
            $table->string('driver', 32);
            $table->string('channel', 16)->nullable();
            $table->string('provider', 32)->nullable();
            $table->string('provider_message_sid', 64)->nullable();
            $table->string('status', 16);
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_delivery_attempts');
    }
};
