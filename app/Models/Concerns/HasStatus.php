<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * For any model with a `status` enum('active','inactive') column — the same
 * convention as `institutes.status`. Use on every tenant-owned entity
 * (Students, Teachers, Batches, Courses, Fees, ...) so activate/deactivate
 * works the same way everywhere, and pair it with a `can_status` permission
 * on the owning module so the action can be granted independently of Edit.
 */
trait HasStatus
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
