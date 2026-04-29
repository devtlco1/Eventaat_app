<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurant_staff_assignments', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('role')->index();
            $table->string('status')->index();
            $table->timestamps();
            $table->index(['restaurant_id', 'branch_id']);
            $table->index(['user_id', 'role']);
        });

        // Enforce uniqueness even when branch_id is NULL (PostgreSQL treats NULLs as distinct).
        DB::statement(
            'CREATE UNIQUE INDEX rsa_user_rest_branch_role_unique ON restaurant_staff_assignments (user_id, restaurant_id, COALESCE(branch_id, 0), role)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS rsa_user_rest_branch_role_unique');
        Schema::dropIfExists('restaurant_staff_assignments');
    }
};
