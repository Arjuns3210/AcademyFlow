<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();

            // Nullable on purpose: a row with institute_id = null is THE
            // platform's own default (demo) number, shared by any institute
            // with no active settings row of its own — same table, same
            // model, same encrypted cast either way, so a later "let an
            // institute self-serve its own WhatsApp number" refinement (per
            // Sprint 9D) is just a new row here, not a schema change.
            $table->foreignId('institute_id')->nullable()->constrained('institutes')->cascadeOnDelete();

            $table->string('phone_number_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('waba_id')->nullable();
            $table->boolean('is_active')->default(false);

            $table->timestamps();

            $table->unique('institute_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
