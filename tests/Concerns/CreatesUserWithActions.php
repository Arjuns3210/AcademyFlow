<?php

namespace Tests\Concerns;

use App\Enums\Role as RoleEnum;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;

/**
 * A tenant user whose role grants exactly the listed actions on one module
 * — used to prove a permission check gates on that SPECIFIC action, not
 * merely "has some permission on this module". Unlike CreatesViewOnlyUser
 * (always view-only, one fixed shape), this lets a test grant any subset of
 * view/add/edit/delete/status on any module, e.g. to build "everything
 * except edit" and confirm only the edit route 403s.
 */
trait CreatesUserWithActions
{
    private function createUserWithActions(int $tenantId, string $moduleSlug, array $actions): User
    {
        $label = $moduleSlug.'-'.($actions ? implode('-', $actions) : 'none');

        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => 'Sweep '.$label,
            'is_system' => false,
        ]);

        $module = Module::where('slug', $moduleSlug)->firstOrFail();

        $role->permissions()->create([
            'module_id' => $module->id,
            'can_view' => in_array('view', $actions, true),
            'can_add' => in_array('add', $actions, true),
            'can_edit' => in_array('edit', $actions, true),
            'can_delete' => in_array('delete', $actions, true),
            'can_status' => in_array('status', $actions, true),
        ]);

        return User::create([
            'tenant_id' => $tenantId,
            'name' => 'Sweep User '.$label,
            'email' => $label.'@example.com',
            'password' => 'password',
            'role' => RoleEnum::TenantUser,
            'role_id' => $role->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
