<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('institutes')->cascadeOnDelete();
            $table->string('student_code');
            $table->string('name');
            $table->string('photo')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            $table->string('parent_name')->nullable();
            $table->string('parent_mobile');
            $table->string('parent_email')->nullable();

            $table->text('address')->nullable();

            // No FK yet — the Course module lands in Sprint 3. Plain nullable
            // column for now so a course can be assigned once it exists,
            // without a second migration to add the column itself.
            $table->unsignedBigInteger('course_id')->nullable();

            // No FK yet — the Batch module lands in Sprint 3. Same treatment.
            $table->unsignedBigInteger('batch_id')->nullable();

            $table->enum('status', ['active', 'inactive', 'dropped'])->default('active');
            $table->date('admission_date');

            $table->softDeletes();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->unique(['tenant_id', 'student_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
