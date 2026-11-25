<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('institutes')->cascadeOnDelete();
            $table->foreignId('student_fee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_mode', ['cash', 'upi', 'card', 'bank_transfer', 'cheque', 'other']);
            $table->date('payment_date');
            $table->string('receipt_number');
            $table->string('remarks')->nullable();

            // nullOnDelete rather than cascade/restrict: staff accounts are
            // only ever deactivated in this app, never hard-deleted, so this
            // is a dormant safety choice — a payment's financial history
            // must survive even in the hypothetical case a user row is gone.
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();

            // Voiding (not deleting) preserves the audit trail — see
            // FeeTransaction::void().
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('student_fee_id');
            $table->index('payment_date');
            $table->unique(['tenant_id', 'receipt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_transactions');
    }
};
