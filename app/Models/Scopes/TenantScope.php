<?php

namespace App\Models\Scopes;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts tenant-owned models to the authenticated user's institute.
 * Super Admins bypass this scope entirely so they can operate across all institutes.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || $user->role === Role::SuperAdmin) {
            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), $user->tenant_id);
    }
}
