<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\Role;
use App\Models\Subscription;

/**
 * Shared institute-setup steps used both by demo seeding and by Super
 * Admin's "create institute" flow (Step 4), so the two never drift apart.
 */
class InstituteProvisioningService
{
    /**
     * Enables every module in the institute's type template for this
     * institute (all enabled, no custom labels — Super Admin can rename or
     * toggle individually afterward). No-op if the institute has no type.
     */
    public function enableModulesFromType(Institute $institute): void
    {
        if (! $institute->institute_type_id) {
            return;
        }

        $moduleIds = $institute->instituteType->modules()->pluck('modules.id');

        foreach ($moduleIds as $moduleId) {
            $institute->instituteModules()->updateOrCreate(
                ['module_id' => $moduleId],
                ['is_enabled' => true]
            );
        }
    }

    /**
     * Creates (or refreshes) the institute's non-deletable "Institute Admin"
     * system role, with full permissions on every module currently enabled
     * for the institute.
     */
    public function createDefaultAdminRole(Institute $institute): Role
    {
        $role = Role::updateOrCreate(
            ['tenant_id' => $institute->id, 'name' => 'Institute Admin'],
            ['is_system' => true]
        );

        $enabledModuleIds = $institute->instituteModules()
            ->where('is_enabled', true)
            ->pluck('module_id');

        foreach ($enabledModuleIds as $moduleId) {
            $role->permissions()->updateOrCreate(
                ['module_id' => $moduleId],
                [
                    'can_view' => true,
                    'can_add' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                    'can_status' => true,
                ]
            );
        }

        return $role;
    }

    /**
     * Every institute starts on a 7-day trial with no plan chosen yet — a
     * commercial plan is only assigned once Super Admin actually activates a
     * subscription (Step 7D), so a trial that lapses unconverted shows a
     * clean plan_id=null rather than implying a purchase decision that was
     * never made.
     *
     * firstOrCreate rather than updateOrCreate: this must be a one-time
     * event. An institute that's already progressed to active/expired/
     * cancelled must never be silently reset back to a fresh trial by a
     * repeat call (e.g. re-running DemoInstituteSeeder).
     */
    public function startTrialSubscription(Institute $institute): Subscription
    {
        return Subscription::firstOrCreate(
            ['institute_id' => $institute->id],
            [
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(7),
            ]
        );
    }
}
