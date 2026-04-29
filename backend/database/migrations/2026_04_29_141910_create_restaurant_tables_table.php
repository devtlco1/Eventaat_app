<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurant_tables', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('seating_area_id')->constrained('seating_areas')->cascadeOnDelete();
            $table->string('label');
            $table->unsignedSmallInteger('capacity')->default(2);
            $table->string('status')->index();
            $table->timestamps();

            $table->unique(['seating_area_id', 'label']);
            $table->index(['seating_area_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};

