<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanStaffLimitFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function superAdmin(): User
    {
        return User::where('email', 'admin@academyflow.in')->firstOrFail();
    }

    public function test_updating_a_plan_with_a_numeric_staff_limit_saves_it(): void
    {
        $superAdmin = $this->superAdmin();
        $plan = Plan::where('name', 'Starter')->firstOrFail();

        $this->actingAs($superAdmin)->put(route('super-admin.plans.update', $plan), [
            'name' => $plan->name, 'price' => $plan->price, 'billing_cycle' => $plan->billing_cycle,
            'staff_limit' => 5,
        ])->assertRedirect(route('super-admin.plans.index'));

        $this->assertSame(5, $plan->fresh()->staff_limit);
    }

    public function test_checking_unlimited_saves_a_null_staff_limit_regardless_of_the_number_field(): void
    {
        $superAdmin = $this->superAdmin();
        $plan = Plan::where('name', 'Starter')->firstOrFail();

        $this->actingAs($superAdmin)->put(route('super-admin.plans.update', $plan), [
            'name' => $plan->name, 'price' => $plan->price, 'billing_cycle' => $plan->billing_cycle,
            'staff_limit' => 5, 'staff_limit_unlimited' => '1',
        ])->assertRedirect(route('super-admin.plans.index'));

        $this->assertNull($plan->fresh()->staff_limit);
    }

    public function test_plans_index_grid_shows_the_current_staff_limit_values(): void
    {
        $superAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)->get(route('super-admin.plans.edit', Plan::where('name', 'Professional')->firstOrFail()));

        $response->assertOk()->assertSee('value="10"', false);
    }
}
