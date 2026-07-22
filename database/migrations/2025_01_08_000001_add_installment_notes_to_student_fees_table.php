<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fees', function (Blueprint $table) {
            // Lightweight, non-enforced reference note for accountants, e.g.
            // "3 installments of Rs 500, due by 5th of each month" — not a
            // due-date/late-fee engine, that's out of v1 scope.
            $table->text('installment_notes')->nullable()->after('installments_allowed');
        });
    }

    public function down(): void
    {
        Schema::table('student_fees', function (Blueprint $table) {
            $table->dropColumn('installment_notes');
        });
    }
};
