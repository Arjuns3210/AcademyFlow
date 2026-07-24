<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\Institute;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use App\Services\StaffLimitService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@brightcoaching.com')->firstOrFail();
    }

    private function addStaff(User $admin, string $email, bool $active = true): User
    {
        $role = Role::where('tenant_id', $admin->tenant_id)->firstOrFail();

        return User::create([
            'tenant_id' => $admin->tenant_id, 'name' => 'Staff '.$email, 'email' => $email,
            'password' => 'password', 'role' => RoleEnum::TenantUser, 'role_id' => $role->id,
            'is_active' => $active, 'email_verified_at' => now(),
        ]);
    }

    public function test_allows_adding_staff_below_the_plan_limit(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        // Starter limit is 3; the founding admin already counts as 1.
        $this->assertTrue(app(StaffLimitService::class)->canAddStaff($admin->institute));
    }

    public function test_blocks_adding_staff_at_the_plan_limit(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        // Founding admin (1) + 2 more = 3, Starter's exact limit.
        $this->addStaff($admin, 'staff1@example.com');
        $this->addStaff($admin, 'staff2@example.com');

        $this->assertFalse(app(StaffLimitService::class)->canAddStaff($admin->institute));
    }

    public function test_deactivating_a_staff_member_frees_their_slot(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $this->addStaff($admin, 'staff1@example.com');
        $toDeactivate = $this->addStaff($admin, 'staff2@example.com');
        $this->assertFalse(app(StaffLimitService::class)->canAddStaff($admin->institute));

        $toDeactivate->update(['is_active' => false]);

        $this->assertTrue(app(StaffLimitService::class)->canAddStaff($admin->institute));
    }

    public function test_enterprise_plan_is_unlimited(): void
    {
        $admin = $this->admin();
        $enterprise = Plan::where('name', 'Enterprise')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $enterprise->id]);

        for ($i = 1; $i <= 20; $i++) {
            $this->addStaff($admin, "enterprise-staff{$i}@example.com");
        }

        $this->assertTrue(app(StaffLimitService::class)->canAddStaff($admin->institute));
    }

    public function test_trial_institute_is_unlimited_even_past_the_professional_limit(): void
    {
        $admin = $this->admin();
        $admin->institute->subscription->update(['status' => 'trial', 'plan_id' => null, 'trial_ends_at' => now()->addDays(5)]);

        for ($i = 1; $i <= 15; $i++) {
            $this->addStaff($admin, "trial-staff{$i}@example.com");
        }

        $this->assertTrue(app(StaffLimitService::class)->canAddStaff($admin->institute));
    }

    public function test_lapsed_trial_blocks_adding_staff_even_if_status_column_is_stale(): void
    {
        $admin = $this->admin();
        $admin->institute->subscription->update(['status' => 'trial', 'plan_id' => null, 'trial_ends_at' => now()->subDay()]);

        $this->assertFalse(app(StaffLimitService::class)->canAddStaff($admin->institute));
    }

    public function test_expired_subscription_blocks_adding_staff(): void
    {
        $admin = $this->admin();
        $enterprise = Plan::where('name', 'Enterprise')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'expired', 'plan_id' => $enterprise->id]);

        $this->assertFalse(app(StaffLimitService::class)->canAddStaff($admin->institute));
    }

    public function test_usage_label_reflects_each_state(): void
    {
        $admin = $this->admin();
        $service = app(StaffLimitService::class);
        $starter = Plan::where('name', 'Starter')->firstOrFail();

        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);
        $this->assertSame('1 / 3 staff used', $service->usageLabel(Institute::find($admin->tenant_id)));

        $this->addStaff($admin, 'a@example.com');
        $this->addStaff($admin, 'b@example.com');
        $this->assertSame('3 / 3 staff used — upgrade to add more', $service->usageLabel(Institute::find($admin->tenant_id)));

        $enterprise = Plan::where('name', 'Enterprise')->firstOrFail();
        $admin->institute->subscription->update(['plan_id' => $enterprise->id]);
        $this->assertSame('3 staff (Unlimited)', $service->usageLabel(Institute::find($admin->tenant_id)));

        $admin->institute->subscription->update(['status' => 'trial', 'plan_id' => null, 'trial_ends_at' => now()->addDays(5)]);
        $this->assertSame('3 staff (Unlimited during trial)', $service->usageLabel(Institute::find($admin->tenant_id)));
    }

    public function test_next_plan_with_higher_limit_is_computed_not_hardcoded(): void
    {
        $service = app(StaffLimitService::class);
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $enterprise = Plan::where('name', 'Enterprise')->firstOrFail();

        $this->assertSame('Professional', $service->nextPlanWithHigherLimit($starter)->name);
        $this->assertSame('Enterprise', $service->nextPlanWithHigherLimit($professional)->name);
        $this->assertNull($service->nextPlanWithHigherLimit($enterprise));
    }
}
