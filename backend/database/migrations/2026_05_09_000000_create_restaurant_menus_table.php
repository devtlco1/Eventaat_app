<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->string('menu_mode')->default('structured');
            $table->string('menu_file_path')->nullable();
            $table->string('menu_url')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index(['restaurant_id', 'display_order']);
            $table->index(['branch_id']);
            $table->index(['status']);
            $table->index(['menu_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_menus');
    }
};
