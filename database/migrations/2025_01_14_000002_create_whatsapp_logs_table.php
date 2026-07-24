<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();

            // nullOnDelete, not cascade — same "history survives" precedent
            // as FeeTransaction.collected_by/voided_by. A log is a record
            // that a message was sent, not a record about the student the
            // way attendance/student_fees rows are, so it should outlive a
            // student being force-deleted rather than vanish with them.
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();

            $table->string('template_name');
            $table->string('recipient_number');

            // 'delivered'/'read' are reserved for a future Meta delivery-
            // webhook receiver, which doesn't exist yet — 9B only ever
            // writes 'sent' or 'failed' at send time.
            $table->enum('status', ['sent', 'delivered', 'failed', 'read'])->default('sent');

            $table->string('meta_message_id')->nullable();

            // Meta's send response never includes per-message cost - actual
            // WhatsApp conversation pricing only surfaces later via billing
            // webhooks/reports, not implemented yet. Left null rather than
            // estimated, so this column stays honest until that's built.
            $table->decimal('cost', 8, 4)->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('institute_id');
            $table->index(['institute_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
