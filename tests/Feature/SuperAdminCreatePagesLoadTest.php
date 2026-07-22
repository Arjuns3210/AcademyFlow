<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A blank "create" page has no model in scope yet — Sprint 7C's own tests
 * only ever posted to store/update/toggle/destroy, so a plain GET to the
 * create page itself was never actually exercised. Caught for real when a
 * user hit /super-admin/coupons/create and it 500'd: the form partial did
 * `$coupon->valid_from?->toDateString()`, where `?->` only guards the
 * toDateString() call, not the `$coupon->valid_from` property access on a
 * $coupon that's null on this exact page. Fixed to `$coupon?->valid_from`.
 * This file is the "does the create page load at all" check that would
 * have caught it, for every Super Admin resource with a create page.
 */
class SuperAdminCreatePagesLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_plan_create_page_loads_without_a_plan_in_scope(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('super-admin.plans.create'));

        $response->assertOk();
        $response->assertSee('Plan Name');
    }

    public function test_coupon_create_page_loads_without_a_coupon_in_scope(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('super-admin.coupons.create'));

        $response->assertOk();
        $response->assertSee('Valid From');
        $response->assertSee('Valid Until');
    }

    public function test_institute_create_page_loads_without_an_institute_in_scope(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('super-admin.institutes.create'));

        $response->assertOk();
        $response->assertSee('Institute Name');
    }

    private function superAdmin(): User
    {
        return User::where('email', 'admin@academyflow.in')->firstOrFail();
    }
}
