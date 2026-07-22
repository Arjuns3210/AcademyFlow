<?php

namespace App\Models\Concerns;

use App\Enums\Role;
use App\Models\Institute;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Applies to every tenant-owned model (Students, Teachers, Courses, Fees, etc.
 * in later sprints). Auto-scopes queries to the current user's tenant_id and
 * auto-fills tenant_id on create, so controllers never have to remember to
 * scope by hand.
 *
 * NEVER apply this to the User model. Resolving the logged-in user from the
 * session queries the users table — if that query's own scope called
 * Auth::user() to decide how to filter, it would recurse into itself
 * indefinitely and hang every authenticated request. User filters tenant_id
 * manually instead; see App\Models\User.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (! $model->tenant_id && Auth::check() && Auth::user()->role !== Role::SuperAdmin) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class, 'tenant_id');
    }
}
