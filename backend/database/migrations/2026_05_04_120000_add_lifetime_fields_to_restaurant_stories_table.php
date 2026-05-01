<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_stories', function (Blueprint $table) {
            $table->string('lifetime_mode')->default('24h')->after('notes');
            $table->unsignedInteger('lifetime_hours')->nullable()->after('lifetime_mode');

            $table->index('lifetime_mode');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_stories', function (Blueprint $table) {
            $table->dropIndex(['lifetime_mode']);
            $table->dropColumn(['lifetime_mode', 'lifetime_hours']);
        });
    }
};
