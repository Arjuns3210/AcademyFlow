<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffLimitEnforcementTest extends TestCase
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

    private function addStaff(User $admin, string $email): User
    {
        $role = Role::where('tenant_id', $admin->tenant_id)->firstOrFail();

        return User::create([
            'tenant_id' => $admin->tenant_id, 'name' => 'Staff '.$email, 'email' => $email,
            'password' => 'password', 'role' => RoleEnum::TenantUser, 'role_id' => $role->id,
            'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    public function test_adding_staff_at_the_limit_is_blocked_with_a_specific_upgrade_message(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);
        $this->addStaff($admin, 'staff1@example.com');
        $this->addStaff($admin, 'staff2@example.com');
        $role = Role::where('tenant_id', $admin->tenant_id)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('institute.staff.store'), [
            'name' => 'One Too Many', 'email' => 'blocked@example.com',
            'role_id' => $role->id, 'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('staff_limit');
        $errors = session('errors');
        $this->assertStringContainsString('Starter plan allows up to 3', $errors->first('staff_limit'));
        $this->assertStringContainsString('Upgrade to Professional for up to 10', $errors->first('staff_limit'));
        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_professional_plan_at_its_limit_points_to_enterprise_not_professional_again(): void
    {
        $admin = $this->admin();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);
        for ($i = 1; $i <= 9; $i++) {
            $this->addStaff($admin, "pro-staff{$i}@example.com");
        }
        $role = Role::where('tenant_id', $admin->tenant_id)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('institute.staff.store'), [
            'name' => 'One Too Many', 'email' => 'blocked2@example.com',
            'role_id' => $role->id, 'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $errors = session('errors');
        $this->assertStringContainsString('Upgrade to Enterprise', $errors->first('staff_limit'));
    }

    public function test_adding_staff_below_the_limit_succeeds_normally(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);
        $role = Role::where('tenant_id', $admin->tenant_id)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('institute.staff.store'), [
            'name' => 'Room For One More', 'email' => 'allowed@example.com',
            'role_id' => $role->id, 'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('institute.staff.index'));
        $this->assertDatabaseHas('users', ['email' => 'allowed@example.com']);
    }

    public function test_deactivating_a_staff_member_lets_a_starter_institute_add_a_replacement(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);
        $this->addStaff($admin, 'staff1@example.com');
        $toDeactivate = $this->addStaff($admin, 'staff2@example.com');

        $this->actingAs($admin)->patch(route('institute.staff.toggle-status', $toDeactivate));

        $role = Role::where('tenant_id', $admin->tenant_id)->firstOrFail();
        $response = $this->actingAs($admin)->post(route('institute.staff.store'), [
            'name' => 'Replacement Staff', 'email' => 'replacement@example.com',
            'role_id' => $role->id, 'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('institute.staff.index'));
        $this->assertDatabaseHas('users', ['email' => 'replacement@example.com']);
    }

    public function test_staff_index_page_shows_the_usage_indicator(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $this->actingAs($admin)->get(route('institute.staff.index'))
            ->assertOk()
            ->assertSee('1 / 3 staff used');
    }
}
