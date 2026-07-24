<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\PlanModule;

/**
 * Singleton (see AppServiceProvider) so its per-plan lookup cache is shared
 * across an entire request, matching PermissionService's precedent — the
 * sidebar, route middleware, and Blade directive can all ask about the same
 * institute/plan multiple times in one request.
 */
class ModuleAccessService
{
    /** @var array<int, array<string, true>> plan_id => set of module_keys */
    private array $planModuleCache = [];

    public function hasAccess(Institute $institute, string $moduleKey): bool
    {
        $subscription = $institute->subscription;

        if (! $subscription) {
            return false;
        }

        if ($subscription->isOnTrial()) {
            // Same live re-check Subscription::loginBlockReason() already
            // does for login — a trial that lapsed since the last
            // subscriptions:expire run shouldn't get free module access
            // just because the status column hasn't caught up yet.
            return ! $subscription->trial_ends_at->isPast();
        }

        if (! $subscription->isActive()) {
            return false;
        }

        return $this->planHasModule($subscription->plan_id, $moduleKey);
    }

    /**
     * Plan names that include the given module - used by the feature-locked
     * page to tell an institute what to upgrade to.
     *
     * @return array<int, string>
     */
    public function plansWithAccess(string $moduleKey): array
    {
        return PlanModule::where('module_key', $moduleKey)
            ->with('plan')
            ->get()
            ->pluck('plan.name')
            ->filter()
            ->values()
            ->all();
    }

    private function planHasModule(?int $planId, string $moduleKey): bool
    {
        if (! $planId) {
            return false;
        }

        if (! isset($this->planModuleCache[$planId])) {
            $this->planModuleCache[$planId] = PlanModule::where('plan_id', $planId)
                ->pluck('module_key')
                ->flip()
                ->map(fn () => true)
                ->all();
        }

        return $this->planModuleCache[$planId][$moduleKey] ?? false;
    }
}
