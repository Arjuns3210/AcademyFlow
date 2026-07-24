<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\Course;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardChartsPlanGateTest extends TestCase
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

    public function test_starter_plan_sees_a_locked_upsell_card_instead_of_the_charts(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Unlock revenue, admission trend &amp; course breakdown charts on the Professional plan.', false)
            ->assertDontSee('id="monthlyCollectionChart"', false)
            ->assertDontSee('id="admissionTrendChart"', false)
            ->assertDontSee('id="studentsByCourseChart"', false);
    }

    public function test_professional_plan_sees_the_real_charts(): void
    {
        $admin = $this->admin();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);

        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Real Chart Course', 'duration' => '1 month', 'fee' => 500]);
        Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Real Chart Student', 'parent_mobile' => '9990001234', 'course_id' => $course->id]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('id="monthlyCollectionChart"', false)
            ->assertSee('id="admissionTrendChart"', false)
            ->assertSee('id="studentsByCourseChart"', false)
            ->assertDontSee('Unlock revenue');
    }

    public function test_trial_institute_sees_the_real_charts(): void
    {
        $admin = $this->admin();
        $admin->institute->subscription->update(['status' => 'trial', 'plan_id' => null, 'trial_ends_at' => now()->addDays(5)]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="monthlyCollectionChart"', false);
    }

    public function test_the_four_summary_cards_stay_visible_on_starter_regardless_of_chart_access(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Students')
            ->assertSee('Active Students')
            ->assertSee('Pending Fees')
            ->assertSee("Today's Collection", false);
    }

    /**
     * A role with neither fees,view nor students,view has nothing to unlock
     * in the first place (Sprint 6E/7E's existing permission gate already
     * nulls both chart datasets) - showing an upsell card there would be
     * misleading, since upgrading the plan wouldn't reveal anything for
     * this role either.
     */
    public function test_a_role_without_chart_permissions_sees_no_locked_card_either(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Gate Course', 'duration' => '1 month', 'fee' => 500]);
        Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Gate Student', 'parent_mobile' => '9990009999', 'course_id' => $course->id]);

        $role = Role::create(['tenant_id' => $admin->tenant_id, 'name' => 'No Chart Access Role', 'is_system' => false]);
        $viewer = User::create([
            'tenant_id' => $admin->tenant_id, 'name' => 'Viewer', 'email' => 'viewer-nograph@example.com',
            'password' => 'password', 'role' => RoleEnum::TenantUser, 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->actingAs($viewer)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Unlock revenue')
            ->assertDontSee('id="monthlyCollectionChart"', false);
    }

    public function test_feature_locked_page_works_for_dashboard_charts_via_the_existing_generic_mechanism(): void
    {
        $admin = $this->admin();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $response = $this->actingAs($admin)->get(route('institute.feature-locked', ['moduleKey' => 'dashboard_charts']));

        $response->assertOk()
            ->assertSee('Dashboard Charts')
            ->assertSee('Professional')
            ->assertSee('Enterprise');
    }
}
