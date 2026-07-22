<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_institute_block_and_unblock_are_logged_against_the_affected_tenant(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $institute = $admin->institute;

        $this->actingAs($superAdmin)->patch(route('super-admin.institutes.toggle-status', $institute));

        $blockLog = ActivityLog::where('action', 'institute_blocked')->latest('id')->firstOrFail();
        $this->assertSame($institute->id, $blockLog->tenant_id, 'must log against the affected institute, not the Super Admin (who has no tenant_id)');

        $this->actingAs($superAdmin)->patch(route('super-admin.institutes.toggle-status', $institute));
        $unblockLog = ActivityLog::where('action', 'institute_unblocked')->latest('id')->firstOrFail();
        $this->assertSame($institute->id, $unblockLog->tenant_id);
    }

    public function test_subscription_activation_and_cancellation_are_logged_against_the_affected_tenant(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $subscription = $admin->institute->subscription;
        $plan = Plan::where('name', 'Starter')->firstOrFail();

        $this->actingAs($superAdmin)->put(route('super-admin.subscriptions.update', $subscription), [
            'plan_id' => $plan->id,
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
        ]);

        $activatedLog = ActivityLog::where('action', 'subscription_activated')->latest('id')->firstOrFail();
        $this->assertSame($admin->tenant_id, $activatedLog->tenant_id);
        $this->assertStringContainsString('Starter', $activatedLog->description);

        $this->actingAs($superAdmin)->patch(route('super-admin.subscriptions.cancel', $subscription));

        $cancelledLog = ActivityLog::where('action', 'subscription_cancelled')->latest('id')->firstOrFail();
        $this->assertSame($admin->tenant_id, $cancelledLog->tenant_id);
    }

    public function test_role_create_and_update_are_logged(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $this->actingAs($admin)->post(route('institute.roles.store'), [
            'name' => 'Audit Test Role',
            'permissions' => [],
        ]);

        $role = Role::where('tenant_id', $admin->tenant_id)->where('name', 'Audit Test Role')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $admin->tenant_id, 'action' => 'role_created', 'subject_id' => $role->id,
        ]);

        $this->actingAs($admin)->put(route('institute.roles.update', $role), [
            'name' => 'Audit Test Role',
            'permissions' => [],
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $admin->tenant_id, 'action' => 'role_updated', 'subject_id' => $role->id,
        ]);
    }

    public function test_staff_activation_and_deactivation_are_logged(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $role = Role::where('tenant_id', $admin->tenant_id)->firstOrFail();
        $staff = User::create([
            'tenant_id' => $admin->tenant_id, 'name' => 'Audit Staff', 'email' => 'audit-staff@example.com',
            'password' => 'password', 'role' => \App\Enums\Role::TenantUser, 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->actingAs($admin)->patch(route('institute.staff.toggle-status', $staff));
        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $admin->tenant_id, 'action' => 'staff_deactivated', 'subject_id' => $staff->id,
        ]);

        $this->actingAs($admin)->patch(route('institute.staff.toggle-status', $staff));
        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $admin->tenant_id, 'action' => 'staff_activated', 'subject_id' => $staff->id,
        ]);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.email'), ['email' => 'admin@brightcoaching.com']);
        }

        $response = $this->post(route('password.email'), ['email' => 'admin@brightcoaching.com']);

        $response->assertStatus(429);
    }
}
