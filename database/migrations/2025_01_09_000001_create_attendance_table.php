<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('institutes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // Nullable — a student can be marked present/absent without
            // being formally in a batch, same as Student's own batch_id.
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();

            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'leave'])->default('present');

            // Nullable + nullOnDelete, matching fee_transactions.collected_by
            // — staff accounts are only ever deactivated in this app, never
            // hard-deleted, so this is a dormant safety choice preserving
            // the attendance record even in that hypothetical case.
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('remarks')->nullable();
            $table->timestamps();

            // One attendance record per student per day — marking again for
            // the same day updates this row (see Attendance::mark()) rather
            // than creating a duplicate.
            $table->unique(['tenant_id', 'student_id', 'attendance_date']);
            $table->index('tenant_id');
            $table->index('attendance_date');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
