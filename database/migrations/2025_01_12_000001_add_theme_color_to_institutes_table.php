<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            // Null means "use the standard dark sidebar" — not every
            // institute picks a custom color, and this must stay
            // distinguishable from having deliberately chosen black.
            $table->string('theme_color', 7)->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->dropColumn('theme_color');
        });
    }
};
