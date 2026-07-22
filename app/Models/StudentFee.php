<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFee extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'total_fee',
        'discount_amount',
        'discount_reason',
        'net_fee',
        'installments_allowed',
        'installment_notes',
    ];

    protected function casts(): array
    {
        return [
            'total_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'net_fee' => 'decimal:2',
            'installments_allowed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $studentFee) {
            // Clamped at 0 — a discount larger than the total fee should
            // never produce a negative amount owed.
            $studentFee->net_fee = max(0, $studentFee->total_fee - $studentFee->discount_amount);
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FeeTransaction::class);
    }

    /**
     * Uses the eager-loaded transactions collection when available rather
     * than always calling transactions() (the relation method, which
     * builds a brand new query every time regardless of any prior
     * with('transactions')) — found during Sprint 8C's N+1 audit: any list
     * of StudentFee rows that eager-loads transactions but reads
     * paid_amount/pending_amount/status per row was still firing one query
     * per row, since this accessor ignored the eager-loaded data entirely.
     */
    protected function paidAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) ($this->relationLoaded('transactions')
                ? $this->transactions->whereNull('voided_at')->sum('amount')
                : $this->transactions()->whereNull('voided_at')->sum('amount')),
        );
    }

    /**
     * Can go negative — e.g. if total_fee is later synced down by a course
     * change after payments were already made against the old, higher fee.
     * That's a real credit-due-to-restructure state, not an error; the UI
     * is responsible for displaying it clearly rather than hiding it.
     */
    protected function pendingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->net_fee - $this->paid_amount,
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->net_fee <= 0) {
                    return 'paid';
                }

                if ($this->paid_amount <= 0) {
                    return 'pending';
                }

                return $this->pending_amount <= 0 ? 'paid' : 'partial';
            },
        );
    }
}
