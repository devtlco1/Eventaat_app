<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_booking_enabled')->default(true);
            $table->unsignedSmallInteger('booking_duration_minutes')->default(90);
            $table->unsignedSmallInteger('min_advance_minutes')->default(60);
            $table->unsignedSmallInteger('max_advance_days')->default(30);
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('mon')->default(true);
            $table->boolean('tue')->default(true);
            $table->boolean('wed')->default(true);
            $table->boolean('thu')->default(true);
            $table->boolean('fri')->default(true);
            $table->boolean('sat')->default(true);
            $table->boolean('sun')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_availability_rules');
    }
};
