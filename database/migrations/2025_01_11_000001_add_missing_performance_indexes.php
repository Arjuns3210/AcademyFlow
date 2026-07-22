<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Found during Sprint 8C's index audit (checked every migration from
 * Sprints 1-7 against the actual database schema, not just the migration
 * source) — both columns are filtered by date range on pages that already
 * exist (Student Report / Fee Report admission-date filters, Collection
 * Report / dashboard chart payment-date filters) but never got an index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('admission_date');
        });

        Schema::table('fee_transactions', function (Blueprint $table) {
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['admission_date']);
        });

        Schema::table('fee_transactions', function (Blueprint $table) {
            $table->dropIndex(['payment_date']);
        });
    }
};
