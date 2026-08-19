<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            // Distinguishes a real Meta Cloud API send (9B/9C) from a manual
            // wa.me link that staff opened by hand — same table, since both
            // are "a reminder was attempted for this student", just via a
            // different channel with different confidence in the outcome.
            $table->enum('source', ['api', 'manual_link'])->default('api')->after('institute_id');
        });

        // A manual wa.me click only proves the link was opened, not that the
        // message was actually sent — reusing 'sent' would overstate what we
        // know, so the enum gets a distinct, honest value for that case.
        DB::statement("ALTER TABLE whatsapp_logs MODIFY status ENUM('sent', 'delivered', 'failed', 'read', 'link_opened') NOT NULL DEFAULT 'sent'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE whatsapp_logs MODIFY status ENUM('sent', 'delivered', 'failed', 'read') NOT NULL DEFAULT 'sent'");

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
