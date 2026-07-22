<?php

namespace Tests\Concerns;

use App\Enums\Role as RoleEnum;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;

/**
 * A tenant user whose role grants view-only access to a single module —
 * used to verify that view-gated pages stay reachable while write actions
 * (add/edit/delete/status) correctly 403 for a role that was never granted
 * them.
 */
trait CreatesViewOnlyUser
{
    private function createViewOnlyUser(int $tenantId, string $moduleSlug): User
    {
        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => ucfirst($moduleSlug).' Viewer Only',
            'is_system' => false,
        ]);

        $module = Module::where('slug', $moduleSlug)->firstOrFail();

        $role->permissions()->create([
            'module_id' => $module->id,
            'can_view' => true,
            'can_add' => false,
            'can_edit' => false,
            'can_delete' => false,
            'can_status' => false,
        ]);

        return User::create([
            'tenant_id' => $tenantId,
            'name' => ucfirst($moduleSlug).' Viewer',
            'email' => "{$moduleSlug}-viewer@example.com",
            'password' => 'password',
            'role' => RoleEnum::TenantUser,
            'role_id' => $role->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
