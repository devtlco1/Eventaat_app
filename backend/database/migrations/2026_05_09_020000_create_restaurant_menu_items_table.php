<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_menu_category_id')->constrained('restaurant_menu_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 8)->default('IQD');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['restaurant_menu_category_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_menu_items');
    }
};
