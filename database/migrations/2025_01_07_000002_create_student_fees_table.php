<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('institutes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_fee', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_reason')->nullable();
            // Recalculated in the model's saving() event, not a DB-generated
            // column — MySQL generated columns complicate updates.
            $table->decimal('net_fee', 10, 2);
            $table->boolean('installments_allowed')->default(false);
            // Lightweight, non-enforced reference note for accountants, e.g.
            // "3 installments of Rs 500, due by 5th of each month" — not a
            // due-date/late-fee engine, that's out of v1 scope.
            $table->text('installment_notes')->nullable();
            $table->timestamps();

            // One row per student for now — assumes one active course per
            // student, matching Sprint 2/3's design. Multi-course-per-student
            // billing (needing one student_fees row per enrollment instead of
            // per student) is a future consideration, not in scope here.
            $table->unique(['tenant_id', 'student_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fees');
    }
};
