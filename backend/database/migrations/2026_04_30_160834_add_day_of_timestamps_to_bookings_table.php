<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dateTime('arrived_at')->nullable()->after('cancelled_at');
            $table->dateTime('seated_at')->nullable()->after('arrived_at');
            $table->dateTime('completed_at')->nullable()->after('seated_at');
            $table->dateTime('no_show_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['arrived_at', 'seated_at', 'completed_at', 'no_show_at']);
        });
    }
};
