<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteType;
use App\Models\Plan;
use App\Models\User;
use App\Services\InstituteProvisioningService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use CreatesSecondInstitute, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_stats_and_mrr_normalize_yearly_plans_to_monthly(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        // Monthly plan paying full price as-is -> contributes 499 to MRR.
        $monthly = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update([
            'status' => 'active', 'plan_id' => $monthly->id, 'amount_paid' => 499,
            'current_period_start' => now()->toDateString(), 'current_period_end' => now()->addMonth()->toDateString(),
        ]);

        // Yearly plan paying 1200 for the year -> normalizes to 100/month.
        [$instituteB] = $this->createSecondInstituteWithAdmin();
        $yearly = Plan::create(['name' => 'Yearly Test Plan', 'price' => 1200, 'billing_cycle' => 'yearly']);
        $instituteB->subscription->update([
            'status' => 'active', 'plan_id' => $yearly->id, 'amount_paid' => 1200,
            'current_period_start' => now()->toDateString(), 'current_period_end' => now()->addYear()->toDateString(),
        ]);

        // A third institute stays on trial — must not count toward active subscriptions or MRR.
        $trialInstitute = Institute::create([
            'institute_type_id' => InstituteType::firstOrFail()->id,
            'name' => 'Still Trialing Institute', 'status' => 'active',
        ]);
        app(InstituteProvisioningService::class)->startTrialSubscription($trialInstitute);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));
        $response->assertOk();

        $response->assertViewHas('superAdminStats', function ($stats) {
            return $stats['totalInstitutes'] === 3
                && $stats['activeSubscriptions'] === 2
                && $stats['trialInstitutes'] === 1
                && $stats['expiredOrCancelled'] === 0
                // 499 (monthly, as-is) + 1200/12 = 100 (yearly, normalized) = 599.
                && abs($stats['mrr'] - 599.0) < 0.001;
        });
    }

    public function test_expired_and_cancelled_subscriptions_count_together_and_are_excluded_from_mrr(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $plan = Plan::where('name', 'Starter')->firstOrFail();

        $admin->institute->subscription->update([
            'status' => 'expired', 'plan_id' => $plan->id, 'amount_paid' => 499,
        ]);

        [$instituteB] = $this->createSecondInstituteWithAdmin();
        $instituteB->subscription->update([
            'status' => 'cancelled', 'plan_id' => $plan->id, 'amount_paid' => 499,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertViewHas('superAdminStats', fn ($stats) => $stats['expiredOrCancelled'] === 2
            && $stats['activeSubscriptions'] === 0
            && $stats['mrr'] === 0.0);
    }

    public function test_institute_type_chart_counts_per_type_and_buckets_untyped_institutes_as_unassigned(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();

        // Seeding already creates one "Coaching Institute" (the demo institute).
        // This adds a second of the same type, one of a different type, and
        // one with no type at all.
        $this->createSecondInstituteWithAdmin();

        $danceAcademy = InstituteType::where('name', 'Dance Academy')->firstOrFail();
        Institute::create(['institute_type_id' => $danceAcademy->id, 'name' => 'Dance Institute', 'status' => 'active']);

        Institute::create(['institute_type_id' => null, 'name' => 'Untyped Institute', 'status' => 'active']);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));
        $response->assertOk();

        $response->assertViewHas('instituteTypeChart', function ($series) {
            $byLabel = collect($series)->pluck('value', 'label');

            return $byLabel['Coaching Institute'] === 2
                && $byLabel['Dance Academy'] === 1
                && $byLabel['Unassigned'] === 1
                // Every institute type from the seeder appears, even ones with
                // zero institutes - matches the Institute Types list page.
                && $byLabel->has('Music Academy')
                && $byLabel['Music Academy'] === 0;
        });
    }
}
