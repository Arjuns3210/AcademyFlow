<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutes', function (Blueprint $table) {
            $table->id();
            // FK to institute_types added in a later migration once that table exists.
            $table->unsignedBigInteger('institute_type_id')->nullable();
            $table->string('name');
            $table->string('logo')->nullable();
            // Null means "use the standard dark sidebar" — not every
            // institute picks a custom color, and this must stay
            // distinguishable from having deliberately chosen black.
            $table->string('theme_color', 7)->nullable();
            $table->text('address')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('gst_number')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('institute_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutes');
    }
};
