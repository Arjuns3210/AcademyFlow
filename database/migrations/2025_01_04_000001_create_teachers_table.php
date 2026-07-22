<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('institutes')->cascadeOnDelete();
            $table->string('name');
            $table->string('qualification')->nullable();
            $table->string('mobile');
            $table->string('email')->nullable();
            // Free-text comma-separated list for now, e.g. "Maths, Physics" —
            // no separate subjects table, per Sprint 3 scope.
            $table->string('subjects')->nullable();
            $table->string('photo')->nullable();

            // Optional link to a Staff login (Sprint 1, Step 5B) for
            // teachers who also need to log in and mark attendance. Nullable
            // and unique: a directory entry doesn't require a login, and a
            // login can't be claimed by more than one teacher record.
            // nullOnDelete (not cascade) — deleting the staff login should
            // unlink, not delete, the teacher directory entry.
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
