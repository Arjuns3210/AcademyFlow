<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\InstituteModule;
use App\Models\Module;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Bound as a singleton (see AppServiceProvider) so its lookups are cached for
 * the lifetime of a single request — a sidebar rendering 10 permission
 * checks hits the DB once per distinct tenant/role involved, not once per
 * check. Being a singleton also means a fresh instance (and therefore a
 * fresh, empty cache) is created per HTTP request and per test, so cached
 * data never leaks between requests or between test cases.
 */
class PermissionService
{
    private const ACTIONS = ['view', 'add', 'edit', 'delete', 'status'];

    /** @var array<int, array<int, true>> tenant_id => set of enabled module_ids */
    private array $enabledModulesCache = [];

    /** @var array<int, Collection<int, RolePermission>> role_id => permissions keyed by module_id */
    private array $rolePermissionsCache = [];

    /** @var array<string, int|null> module slug => module_id */
    private array $moduleIdCache = [];

    public function can(User $user, string $moduleSlug, string $action): bool
    {
        if (! in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException(
                "Invalid permission action [{$action}]. Expected one of: ".implode(', ', self::ACTIONS)
            );
        }

        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        if (! $user->tenant_id) {
            return false;
        }

        $moduleId = $this->resolveModuleId($moduleSlug);

        if (! $moduleId || ! $this->isModuleEnabled($user->tenant_id, $moduleId)) {
            return false;
        }

        $permission = $this->permissionFor($user->role_id, $moduleId);

        if (! $permission) {
            return false;
        }

        return (bool) $permission->{"can_{$action}"};
    }

    private function resolveModuleId(string $slug): ?int
    {
        if (! array_key_exists($slug, $this->moduleIdCache)) {
            $this->moduleIdCache[$slug] = Module::where('slug', $slug)->value('id');
        }

        return $this->moduleIdCache[$slug];
    }

    private function isModuleEnabled(int $tenantId, int $moduleId): bool
    {
        if (! isset($this->enabledModulesCache[$tenantId])) {
            $this->enabledModulesCache[$tenantId] = InstituteModule::query()
                ->where('institute_id', $tenantId)
                ->where('is_enabled', true)
                ->pluck('module_id')
                ->flip()
                ->all();
        }

        return isset($this->enabledModulesCache[$tenantId][$moduleId]);
    }

    private function permissionFor(?int $roleId, int $moduleId): ?RolePermission
    {
        if (! $roleId) {
            return null;
        }

        if (! isset($this->rolePermissionsCache[$roleId])) {
            $this->rolePermissionsCache[$roleId] = RolePermission::where('role_id', $roleId)
                ->get()
                ->keyBy('module_id');
        }

        return $this->rolePermissionsCache[$roleId]->get($moduleId);
    }
}
