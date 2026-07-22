<?php

namespace App\View\Composers;

use App\Enums\Role;
use App\Models\InstituteModule;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Computes which of the institute's enabled modules the logged-in user's
 * role actually has view permission on — the intersection that decides
 * what shows in the tenant sidebar. Empty for guests, Super Admin, or a
 * user with no tenant (none of whom use this layout in practice).
 */
class TenantSidebarComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        if (! $user || $user->role === Role::SuperAdmin || ! $user->tenant_id) {
            $view->with('sidebarModules', collect());

            return;
        }

        $modules = InstituteModule::where('institute_id', $user->tenant_id)
            ->where('is_enabled', true)
            ->with('module')
            ->get()
            ->filter(fn ($instituteModule) => $user->canModule($instituteModule->module->slug, 'view'))
            ->sortBy(fn ($instituteModule) => $instituteModule->module->sort_order)
            ->values();

        $view->with('sidebarModules', $modules);
    }
}
