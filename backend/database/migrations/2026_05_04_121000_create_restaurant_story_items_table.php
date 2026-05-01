<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_story_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_story_id')->constrained('restaurant_stories')->cascadeOnDelete();
            $table->string('item_type');
            $table->string('media_path')->nullable();
            $table->text('body')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('item_duration_seconds')->nullable();
            $table->timestamps();

            $table->index('restaurant_story_id');
            $table->index('item_type');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_story_items');
    }
};
