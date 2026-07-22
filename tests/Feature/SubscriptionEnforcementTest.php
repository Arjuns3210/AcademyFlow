<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_active_subscription_logs_in_normally(): void
    {
        $admin = $this->tenantAdmin();
        $admin->institute->subscription->update(['status' => 'active']);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_trial_still_within_window_logs_in_normally(): void
    {
        $admin = $this->tenantAdmin();
        $admin->institute->subscription->update(['status' => 'trial', 'trial_ends_at' => now()->addDays(3)]);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_trial_past_its_end_date_is_blocked_even_if_status_column_is_stale(): void
    {
        $admin = $this->tenantAdmin();
        // status is still 'trial' — simulates subscriptions:expire not having run yet today.
        $admin->institute->subscription->update(['status' => 'trial', 'trial_ends_at' => now()->subHour()]);

        $response = $this->post(route('login'), ['email' => $admin->email, 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => 'Your free trial has ended. Please contact us to activate a subscription.']);
        $this->assertGuest();
    }

    public function test_expired_subscription_is_blocked(): void
    {
        $admin = $this->tenantAdmin();
        $admin->institute->subscription->update(['status' => 'expired']);

        $response = $this->post(route('login'), ['email' => $admin->email, 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => 'Your subscription has expired. Please contact us to renew.']);
        $this->assertGuest();
    }

    public function test_cancelled_subscription_is_blocked(): void
    {
        $admin = $this->tenantAdmin();
        $admin->institute->subscription->update(['status' => 'cancelled']);

        $response = $this->post(route('login'), ['email' => $admin->email, 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => 'Your subscription has been cancelled. Please contact support.']);
        $this->assertGuest();
    }

    public function test_active_subscription_past_current_period_end_still_logs_in_the_documented_gap(): void
    {
        $admin = $this->tenantAdmin();
        $admin->institute->subscription->update([
            'status' => 'active',
            'current_period_end' => now()->subDays(2)->toDateString(),
        ]);

        // Documented asymmetry (confirmed with the user before building
        // Step 7B): active trusts the stored status alone until the daily
        // command catches it — only trial gets a live re-check at login.
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_super_admin_login_is_entirely_unaffected(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();

        $this->post(route('login'), ['email' => $superAdmin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_expire_subscriptions_command_flips_lapsed_trial_and_active_but_leaves_others_alone(): void
    {
        $lapsedTrial = $this->subscriptionFor('Lapsed Trial Institute', ['status' => 'trial', 'trial_ends_at' => now()->subDay()]);
        $freshTrial = $this->subscriptionFor('Fresh Trial Institute', ['status' => 'trial', 'trial_ends_at' => now()->addDay()]);
        $lapsedActive = $this->subscriptionFor('Lapsed Active Institute', ['status' => 'active', 'current_period_end' => now()->subDay()->toDateString()]);
        $currentActive = $this->subscriptionFor('Current Active Institute', ['status' => 'active', 'current_period_end' => now()->addDay()->toDateString()]);
        $alreadyCancelled = $this->subscriptionFor('Cancelled Institute', ['status' => 'cancelled']);

        $this->artisan('subscriptions:expire')->assertExitCode(0);

        $this->assertSame('expired', $lapsedTrial->fresh()->status);
        $this->assertSame('trial', $freshTrial->fresh()->status);
        $this->assertSame('expired', $lapsedActive->fresh()->status);
        $this->assertSame('active', $currentActive->fresh()->status);
        $this->assertSame('cancelled', $alreadyCancelled->fresh()->status);
    }

    public function test_expire_subscriptions_command_is_idempotent_when_run_twice(): void
    {
        $lapsedTrial = $this->subscriptionFor('Idempotency Institute', ['status' => 'trial', 'trial_ends_at' => now()->subDay()]);

        $this->artisan('subscriptions:expire');
        $firstRunUpdatedAt = $lapsedTrial->fresh()->updated_at;

        $this->artisan('subscriptions:expire');
        $secondRunSubscription = $lapsedTrial->fresh();

        $this->assertSame('expired', $secondRunSubscription->status);
        $this->assertTrue(
            $firstRunUpdatedAt->equalTo($secondRunSubscription->updated_at),
            'a second run must not touch an already-expired row'
        );
    }

    private function tenantAdmin(): User
    {
        return User::where('email', 'admin@brightcoaching.com')->firstOrFail();
    }

    private function subscriptionFor(string $instituteName, array $subscriptionAttributes): Subscription
    {
        $institute = Institute::create([
            'institute_type_id' => \App\Models\InstituteType::firstOrFail()->id,
            'name' => $instituteName,
            'status' => 'active',
        ]);

        return Subscription::create(array_merge([
            'institute_id' => $institute->id,
            'trial_ends_at' => now()->addDays(7),
        ], $subscriptionAttributes));
    }
}
