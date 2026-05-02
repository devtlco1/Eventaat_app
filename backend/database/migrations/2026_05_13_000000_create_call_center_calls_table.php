<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_center_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('support_ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction');
            $table->string('reason');
            $table->string('outcome')->default('pending');
            $table->string('phone')->nullable();
            $table->string('caller_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('restaurant_id');
            $table->index('booking_id');
            $table->index('support_ticket_id');
            $table->index('customer_user_id');
            $table->index('handled_by_user_id');
            $table->index('direction');
            $table->index('reason');
            $table->index('outcome');
            $table->index('follow_up_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_center_calls');
    }
};
