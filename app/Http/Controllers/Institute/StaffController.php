<?php

namespace App\Http\Controllers\Institute;

use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institute\StoreStaffRequest;
use App\Http\Requests\Institute\UpdateStaffRequest;
use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\Role;
use App\Models\User;
use App\Services\StaffLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(StaffLimitService $staffLimit): View
    {
        $staff = User::where('tenant_id', Auth::user()->tenant_id)
            ->with('roleRecord')
            ->orderBy('name')
            ->get();

        return view('institute.staff.index', [
            'staff' => $staff,
            'staffUsageLabel' => $staffLimit->usageLabel(Auth::user()->institute),
        ]);
    }

    public function create(): View
    {
        return view('institute.staff.create', [
            'roles' => $this->tenantRoles(),
        ]);
    }

    public function store(StoreStaffRequest $request, StaffLimitService $staffLimit): RedirectResponse
    {
        $institute = Auth::user()->institute;

        if (! $staffLimit->canAddStaff($institute)) {
            return back()->withInput()->withErrors([
                'staff_limit' => $this->staffLimitMessage($institute, $staffLimit),
            ]);
        }

        User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => RoleEnum::TenantUser,
            'role_id' => $request->validated('role_id'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('institute.staff.index')->with('status', 'Staff member created.');
    }

    public function edit(User $user): View
    {
        $this->assertSameTenant($user);

        return view('institute.staff.edit', [
            'staffMember' => $user,
            'roles' => $this->tenantRoles(),
        ]);
    }

    public function update(UpdateStaffRequest $request, User $user): RedirectResponse
    {
        $this->assertSameTenant($user);

        $user->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role_id' => $request->validated('role_id'),
        ]);

        return redirect()->route('institute.staff.index')->with('status', 'Staff member updated.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->assertSameTenant($user);

        abort_if($user->id === Auth::id(), 403, 'You cannot deactivate your own account.');

        $user->update(['is_active' => ! $user->is_active]);

        ActivityLog::record(
            $user->is_active ? 'staff_activated' : 'staff_deactivated',
            $user,
            "\"{$user->name}\" was ".($user->is_active ? 'activated' : 'deactivated').'.'
        );

        return back()->with('status', "\"{$user->name}\" is now ".($user->is_active ? 'active.' : 'deactivated.'));
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->assertSameTenant($user);

        $newPassword = Str::password(12);

        $user->update(['password' => $newPassword]);

        return redirect()->route('institute.staff.edit', $user)->with('newStaffPassword', $newPassword);
    }

    /**
     * User has no tenant global scope (by design — see the User model's
     * docblock), so route-model binding on {user} doesn't automatically
     * confine it to the current institute. Every action that receives a
     * User must check this explicitly or a staff member could edit/reset/
     * deactivate another institute's users just by guessing an ID.
     */
    private function assertSameTenant(User $user): void
    {
        abort_unless($user->tenant_id === Auth::user()->tenant_id, 404);
    }

    private function tenantRoles()
    {
        return Role::orderBy('name')->get();
    }

    /**
     * Names the actual current plan/limit and the actual next plan that
     * would raise it, rather than a generic error - both computed live so
     * this stays correct if Super Admin ever reorders/renames/re-prices plans.
     */
    private function staffLimitMessage(Institute $institute, StaffLimitService $staffLimit): string
    {
        $plan = $institute->subscription?->plan;
        $limit = $plan?->staff_limit;
        $planName = $plan?->name ?? 'current';

        $message = $limit === null
            ? "Your {$planName} plan has a staff limit."
            : "Your {$planName} plan allows up to {$limit} staff ".Str::plural('member', $limit).'.';

        $nextPlan = $staffLimit->nextPlanWithHigherLimit($plan);

        if ($nextPlan) {
            $message .= $nextPlan->staff_limit === null
                ? " Upgrade to {$nextPlan->name} for unlimited staff."
                : " Upgrade to {$nextPlan->name} for up to {$nextPlan->staff_limit}.";
        } else {
            $message .= ' Contact us to increase your limit.';
        }

        return $message;
    }
}
