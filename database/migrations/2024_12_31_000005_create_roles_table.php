<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            // Every role belongs to exactly one institute. Super Admin has no
            // Role row at all — their access is granted via users.role, not
            // role_id, so this column is never null in practice.
            $table->foreignId('tenant_id')->constrained('institutes')->cascadeOnDelete();
            $table->string('name');
            // Protects the auto-seeded "Institute Admin" role per institute from edit/delete.
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
