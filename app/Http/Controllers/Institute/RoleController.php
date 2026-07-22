<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institute\StoreRoleRequest;
use App\Http\Requests\Institute\UpdateRoleRequest;
use App\Models\ActivityLog;
use App\Models\InstituteModule;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        // Role uses BelongsToTenant, so this is already scoped to the
        // logged-in user's institute via the global TenantScope.
        $roles = Role::withCount('users')->orderBy('name')->get();

        return view('institute.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $enabledModules = $this->enabledModules();

        return view('institute.roles.create', [
            'enabledModules' => $enabledModules,
            'existingPermissions' => collect(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->validated('name'),
            'is_system' => false,
        ]);

        $this->syncPermissions($role, $request->input('permissions', []));

        ActivityLog::record('role_created', $role, "Role \"{$role->name}\" was created.");

        return redirect()->route('institute.roles.index')->with('status', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role): View
    {
        abort_if($role->is_system, 403, 'System roles cannot be edited.');

        return view('institute.roles.edit', [
            'role' => $role,
            'enabledModules' => $this->enabledModules(),
            'existingPermissions' => $role->permissions()->get()->keyBy('module_id'),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403, 'System roles cannot be edited.');

        $role->update(['name' => $request->validated('name')]);

        $this->syncPermissions($role, $request->input('permissions', []));

        ActivityLog::record('role_updated', $role, "Role \"{$role->name}\" permissions were updated.");

        return redirect()->route('institute.roles.index')->with('status', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403, 'System roles cannot be deleted.');

        $usersCount = $role->users()->count();

        if ($usersCount > 0) {
            return back()->withErrors([
                'role' => "Cannot delete this role — it is currently assigned to {$usersCount} user(s).",
            ]);
        }

        $role->delete();

        return redirect()->route('institute.roles.index')->with('status', "Role \"{$role->name}\" deleted.");
    }

    /**
     * This institute's enabled modules — InstituteModule has no tenant
     * global scope, so tenant_id is filtered explicitly here.
     */
    private function enabledModules()
    {
        return InstituteModule::where('institute_id', Auth::user()->tenant_id)
            ->where('is_enabled', true)
            ->with('module')
            ->get();
    }

    private function syncPermissions(Role $role, array $permissions): void
    {
        $enabledModuleIds = InstituteModule::where('institute_id', Auth::user()->tenant_id)
            ->where('is_enabled', true)
            ->pluck('module_id');

        foreach ($enabledModuleIds as $moduleId) {
            $modulePerms = $permissions[$moduleId] ?? [];

            $role->permissions()->updateOrCreate(
                ['module_id' => $moduleId],
                [
                    'can_view' => ! empty($modulePerms['view']),
                    'can_add' => ! empty($modulePerms['add']),
                    'can_edit' => ! empty($modulePerms['edit']),
                    'can_delete' => ! empty($modulePerms['delete']),
                    'can_status' => ! empty($modulePerms['status']),
                ]
            );
        }
    }
}
