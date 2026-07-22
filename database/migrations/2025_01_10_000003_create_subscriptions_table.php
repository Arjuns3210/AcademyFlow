<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // One subscription row per institute, mutated in place over its
            // lifetime (trial -> active -> expired -> active again on
            // renewal, ...) rather than one row per billing period — the
            // unique constraint keeps "the institute's subscription" an
            // unambiguous lookup everywhere it's queried.
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();

            // Null while on trial — no commercial plan has been chosen yet.
            // Only set once Super Admin actually activates a subscription.
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();

            $table->enum('status', ['trial', 'active', 'expired', 'cancelled'])->default('trial');

            $table->dateTime('trial_ends_at');
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();

            $table->timestamps();

            $table->unique('institute_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
