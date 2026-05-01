<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_menu_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_menu_id')->constrained('restaurant_menus')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['restaurant_menu_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_menu_categories');
    }
};
