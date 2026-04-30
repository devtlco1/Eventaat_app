<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('restaurant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('seating_area_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('restaurant_table_id')
                ->nullable()
                ->constrained('restaurant_tables')
                ->nullOnDelete();

            $table->dateTime('starts_at');
            $table->unsignedSmallInteger('party_size');
            $table->string('status', 32);

            $table->text('customer_note')->nullable();
            $table->text('restaurant_note')->nullable();

            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('customer_id');
            $table->index('restaurant_id');
            $table->index('branch_id');
            $table->index('status');
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

