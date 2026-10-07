<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Services\ModuleAccessService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_active_subscription_on_a_plan_with_the_module_grants_access(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);

        $service = app(ModuleAccessService::class);

        $this->assertTrue($service->hasAccess($admin->institute, 'whatsapp_reminder'));
        $this->assertTrue($service->hasAccess($admin->institute, 'dashboard_charts'));
    }

    public function test_active_subscription_on_a_plan_without_the_module_denies_access(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);

        // Professional includes whatsapp_reminder/dashboard_charts but not these three.
        $service = app(ModuleAccessService::class);

        $this->assertFalse($service->hasAccess($admin->institute, 'qr_attendance'));
        $this->assertFalse($service->hasAccess($admin->institute, 'student_login'));
        $this->assertFalse($service->hasAccess($admin->institute, 'parent_login'));
    }

    public function test_active_subscription_on_starter_plan_has_no_module_access_at_all(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $service = app(ModuleAccessService::class);

        foreach (['whatsapp_reminder', 'qr_attendance', 'student_login', 'parent_login'] as $key) {
            $this->assertFalse($service->hasAccess($admin->institute, $key), "Expected Starter to lack {$key}");
        }
    }

    public function test_trial_institute_gets_full_access_to_every_module_regardless_of_plan(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        // No plan chosen yet — exactly how a fresh trial actually looks (Sprint 7A).
        $admin->institute->subscription->update([
            'status' => 'trial', 'plan_id' => null, 'trial_ends_at' => now()->addDays(5),
        ]);

        $service = app(ModuleAccessService::class);

        foreach (['whatsapp_reminder', 'qr_attendance', 'student_login', 'parent_login'] as $key) {
            $this->assertTrue($service->hasAccess($admin->institute, $key), "Expected trial to grant {$key}");
        }
    }

    public function test_lapsed_trial_denies_access_even_if_status_column_is_stale(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        // Same "live re-check" scenario as Subscription::loginBlockReason() -
        // status hasn't been flipped to 'expired' by tonight's cron yet, but
        // the trial window itself has already passed.
        $admin->institute->subscription->update([
            'status' => 'trial', 'plan_id' => null, 'trial_ends_at' => now()->subDay(),
        ]);

        $service = app(ModuleAccessService::class);

        $this->assertFalse($service->hasAccess($admin->institute, 'whatsapp_reminder'));
    }

    public function test_expired_subscription_denies_access(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $enterprise = Plan::where('name', 'Enterprise')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'expired', 'plan_id' => $enterprise->id]);

        // Even though Enterprise includes everything, expired means no access.
        $service = app(ModuleAccessService::class);

        $this->assertFalse($service->hasAccess($admin->institute, 'whatsapp_reminder'));
    }

    public function test_cancelled_subscription_denies_access(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $enterprise = Plan::where('name', 'Enterprise')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'cancelled', 'plan_id' => $enterprise->id]);

        $service = app(ModuleAccessService::class);

        $this->assertFalse($service->hasAccess($admin->institute, 'whatsapp_reminder'));
    }
}
