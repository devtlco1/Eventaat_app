<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('story_type')->default('image');
            $table->string('media_url')->nullable();
            $table->text('body')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('display_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('restaurant_id');
            $table->index('branch_id');
            $table->index('status');
            $table->index('story_type');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_stories');
    }
};

