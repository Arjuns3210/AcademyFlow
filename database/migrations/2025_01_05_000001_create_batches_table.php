<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('institutes')->cascadeOnDelete();
            $table->string('name');

            // Courses use SoftDeletes, so this only matters for the rare
            // forceDelete() case — restrict rather than cascade, so a hard
            // course delete can't silently take batches (and their history)
            // out with it.
            $table->foreignId('course_id')->constrained()->restrictOnDelete();

            // Optional — a batch can exist before a teacher is assigned.
            // nullOnDelete: losing the teacher record unassigns the batch
            // rather than blocking or cascading.
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();

            $table->string('time_slot');
            $table->unsignedInteger('capacity')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
