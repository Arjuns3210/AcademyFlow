<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class FeeTransaction extends Model
{
    use BelongsToTenant;

    /**
     * Attempts before generateUniqueReceiptNumber() gives up guessing and
     * falls back to a random suffix — mirrors Student's student_code
     * generation; see performInsert() below.
     */
    private const MAX_RECEIPT_ATTEMPTS = 5;

    protected $fillable = [
        'tenant_id',
        'student_fee_id',
        'amount',
        'payment_mode',
        'payment_date',
        'receipt_number',
        'remarks',
        'collected_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $transaction) {
            if (! $transaction->payment_date) {
                $transaction->payment_date = now()->toDateString();
            }

            if (! $transaction->receipt_number) {
                $transaction->receipt_number = static::generateUniqueReceiptNumber($transaction);
            }
        });
    }

    public function studentFee(): BelongsTo
    {
        return $this->belongsTo(StudentFee::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function paymentModeLabel(): string
    {
        return match ($this->payment_mode) {
            'upi' => 'UPI',
            'bank_transfer' => 'Bank Transfer',
            default => ucfirst($this->payment_mode),
        };
    }

    /**
     * Voids rather than deletes — voided transactions stay visible in the
     * history table (struck through, badged) and are simply excluded from
     * the paid_amount sum, preserving the financial audit trail.
     *
     * Sets attributes directly rather than update() — voided_at/voided_by/
     * void_reason are deliberately excluded from $fillable so a controller
     * can never mass-assign void status by accident, and update() is mass
     * assignment too, so it would silently no-op on these same fields.
     */
    public function void(User $by, string $reason): void
    {
        $this->voided_at = now();
        $this->voided_by = $by->id;
        $this->void_reason = $reason;
        $this->save();
    }

    /**
     * Same race-condition-safe retry approach as Student::performInsert() —
     * the creating() hook's guess is good-faith, this catches a genuine
     * unique-constraint collision from a concurrent submission and retries
     * with a fresh number instead of failing the whole payment.
     */
    protected function performInsert(Builder $query)
    {
        for ($attempt = 1; $attempt <= self::MAX_RECEIPT_ATTEMPTS; $attempt++) {
            try {
                return parent::performInsert($query);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === self::MAX_RECEIPT_ATTEMPTS || ! str_contains($e->getMessage(), 'receipt_number')) {
                    throw $e;
                }

                $this->receipt_number = static::generateUniqueReceiptNumber($this);
            }
        }

        return false;
    }

    private static function generateUniqueReceiptNumber(self $transaction): string
    {
        $institute = $transaction->tenant_id ? Institute::find($transaction->tenant_id) : null;
        $shortCode = $institute?->shortCode() ?? 'ST';

        $baseCount = static::withoutGlobalScopes()
            ->where('tenant_id', $transaction->tenant_id)
            ->where('receipt_number', 'like', "{$shortCode}-RCT-%")
            ->count();

        for ($offset = 1; $offset <= self::MAX_RECEIPT_ATTEMPTS; $offset++) {
            $candidate = sprintf('%s-RCT-%06d', $shortCode, $baseCount + $offset);

            $exists = static::withoutGlobalScopes()
                ->where('tenant_id', $transaction->tenant_id)
                ->where('receipt_number', $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        // Vanishingly unlikely: every guessed sequence number was taken.
        return sprintf('%s-RCT-%s', $shortCode, Str::upper(Str::random(6)));
    }
}
