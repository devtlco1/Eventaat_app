<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 12B introduces new statuses but keeps the same column.
        // Existing values remain valid; this is a safe no-op backfill to normalize unexpected legacy values.
        DB::table('restaurant_offers')
            ->whereNotIn('status', [
                'draft',
                'pending_review',
                'published',
                'rejected',
                'expired',
                'cancelled',
            ])
            ->update(['status' => 'draft']);
    }

    public function down(): void
    {
        // No rollback needed; statuses are string-backed and forward compatible.
    }
};

