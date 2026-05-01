<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('restaurant_menu_items')) {
            return;
        }

        Schema::table('restaurant_menu_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('restaurant_menu_items', 'image_path')) {
                $table->string('image_path', 2048)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('restaurant_menu_items')) {
            return;
        }

        Schema::table('restaurant_menu_items', function (Blueprint $table): void {
            if (Schema::hasColumn('restaurant_menu_items', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
