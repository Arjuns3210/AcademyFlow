<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_id',
        'plan_id',
        'coupon_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'amount_paid',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Null means login may proceed. The trial branch re-checks trial_ends_at
     * live, on top of the stored status — so a trial that lapsed since the
     * last run of subscriptions:expire is still caught immediately, not
     * after tonight's cron. expired/cancelled trust the stored status alone.
     */
    public function loginBlockReason(): ?string
    {
        if ($this->status === 'trial' && $this->trial_ends_at->isPast()) {
            return 'Your free trial has ended. Please contact us to activate a subscription.';
        }

        if ($this->status === 'expired') {
            return 'Your subscription has expired. Please contact us to renew.';
        }

        if ($this->status === 'cancelled') {
            return 'Your subscription has been cancelled. Please contact support.';
        }

        return null;
    }

    /**
     * Compares calendar dates, not exact timestamps — diffing the raw
     * datetimes floors to a day short for almost the entire final day (e.g.
     * a trial created with trial_ends_at = now()->addDays(3) already reads
     * as "2 days left" a few seconds later, the moment any time at all has
     * elapsed). Stripping both sides to midnight keeps the count stable for
     * the whole calendar day and only ticks down at an actual day boundary.
     *
     * absolute: true — diffInDays() is signed by argument order in this
     * Carbon version (confirmed in Sprint 6B), so this must be explicit or
     * a lapsed-looking negative value could slip through silently.
     */
    public function daysLeftInTrial(): int
    {
        return now()->startOfDay()->diffInDays($this->trial_ends_at->copy()->startOfDay(), absolute: true);
    }
}
