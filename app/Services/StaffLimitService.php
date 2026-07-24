<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\Plan;
use App\Models\User;

class StaffLimitService
{
    /**
     * Trial gets unlimited staff, same policy as ModuleAccessService's
     * module access — consistency across the whole plan-gating system was
     * the deciding factor (confirmed with the user), even though the
     * exposure this bounds (seats used, not a per-message cost) is a
     * different kind of risk than Sprint 9A's WhatsApp trial policy.
     * Same live trial_ends_at re-check as ModuleAccessService/
     * loginBlockReason(), so a lapsed-but-not-yet-cron'd trial isn't
     * treated as still on trial.
     */
    public function canAddStaff(Institute $institute): bool
    {
        $subscription = $institute->subscription;

        if (! $subscription) {
            return false;
        }

        if ($subscription->isOnTrial()) {
            return ! $subscription->trial_ends_at->isPast();
        }

        if (! $subscription->isActive()) {
            return false;
        }

        $limit = $subscription->plan?->staff_limit;

        if ($limit === null) {
            return true;
        }

        return $this->currentActiveStaffCount($institute) < $limit;
    }

    /**
     * Counts every active tenant_user row for this institute — the same
     * set StaffController::index() already lists, which includes the
     * founding Institute Admin (not specially excluded there either) — plus
     * an is_active filter, since deactivating someone frees their slot
     * without needing an actual delete (Users have neither soft-deletes nor
     * a destroy() route at all today).
     */
    public function currentActiveStaffCount(Institute $institute): int
    {
        return User::where('tenant_id', $institute->id)->where('is_active', true)->count();
    }

    /**
     * Human-readable usage summary for the Staff list page, e.g.
     * "3 / 3 staff used — upgrade to add more", "7 / 10 staff used",
     * "5 staff (Unlimited)" — so Institute Admin sees this before hitting
     * the wall, not just after.
     */
    public function usageLabel(Institute $institute): string
    {
        $count = $this->currentActiveStaffCount($institute);
        $subscription = $institute->subscription;

        if ($subscription?->isOnTrial()) {
            return "{$count} staff (Unlimited during trial)";
        }

        $limit = $subscription?->plan?->staff_limit;

        if ($limit === null) {
            return "{$count} staff (Unlimited)";
        }

        $label = "{$count} / {$limit} staff used";

        return $count >= $limit ? "{$label} — upgrade to add more" : $label;
    }

    /**
     * The cheapest active plan that would actually raise this institute's
     * limit - computed rather than hardcoded, so the upgrade message stays
     * correct if plans are ever reordered/renamed/re-priced, and so a
     * Professional institute hitting its cap is correctly pointed at
     * Enterprise rather than at Professional again.
     */
    public function nextPlanWithHigherLimit(?Plan $currentPlan): ?Plan
    {
        $currentLimit = $currentPlan?->staff_limit;

        // Current plan is already unlimited - nothing raises that further.
        if ($currentPlan && $currentLimit === null) {
            return null;
        }

        return Plan::where('is_active', true)
            ->when($currentPlan, fn ($query) => $query->where('price', '>', $currentPlan->price))
            ->orderBy('price')
            ->get()
            ->first(fn (Plan $plan) => $plan->staff_limit === null || $plan->staff_limit > $currentLimit);
    }
}
