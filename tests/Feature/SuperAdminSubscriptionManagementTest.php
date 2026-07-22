<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_valid_flat_coupon_discounts_amount_and_increments_times_used(): void
    {
        $subscription = $this->tenantAdmin()->institute->subscription;
        $plan = Plan::where('name', 'Starter')->firstOrFail(); // 499.00
        $coupon = Coupon::create([
            'code' => 'FLAT50', 'discount_type' => 'flat', 'discount_value' => 50,
            'valid_from' => now()->subDay()->toDateString(), 'valid_until' => now()->addDay()->toDateString(),
            'max_uses' => 5, 'times_used' => 2,
        ]);

        $this->actingAs($this->superAdmin())->put(route('super-admin.subscriptions.update', $subscription), [
            'plan_id' => $plan->id,
            'coupon_code' => 'FLAT50',
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
        ])->assertRedirect(route('super-admin.subscriptions.index'));

        $subscription->refresh();
        $this->assertSame('449.00', $subscription->amount_paid); // 499 - 50
        $this->assertSame($coupon->id, $subscription->coupon_id);
        $this->assertSame(3, $coupon->fresh()->times_used);
    }

    public function test_valid_percentage_coupon_discounts_amount_correctly(): void
    {
        $subscription = $this->tenantAdmin()->institute->subscription;
        $plan = Plan::where('name', 'Starter')->firstOrFail(); // 499.00
        Coupon::create([
            'code' => 'SAVE20', 'discount_type' => 'percentage', 'discount_value' => 20,
            'valid_from' => now()->subDay()->toDateString(), 'valid_until' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->superAdmin())->put(route('super-admin.subscriptions.update', $subscription), [
            'plan_id' => $plan->id,
            'coupon_code' => 'SAVE20',
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
        ])->assertRedirect(route('super-admin.subscriptions.index'));

        // 499 * 0.8 = 399.20
        $this->assertSame('399.20', $subscription->fresh()->amount_paid);
    }

    public function test_nonexistent_coupon_code_is_rejected_and_nothing_changes(): void
    {
        $subscription = $this->tenantAdmin()->institute->subscription;

        $response = $this->activateWithCoupon($subscription, 'NOPE');

        $response->assertSessionHasErrors('coupon_code');
        $this->assertSame('trial', $subscription->fresh()->status);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $subscription = $this->tenantAdmin()->institute->subscription;
        Coupon::create([
            'code' => 'OLDONE', 'discount_type' => 'flat', 'discount_value' => 50,
            'valid_from' => now()->subMonth()->toDateString(), 'valid_until' => now()->subDay()->toDateString(),
        ]);

        $this->activateWithCoupon($subscription, 'OLDONE')->assertSessionHasErrors('coupon_code');
        $this->assertSame('trial', $subscription->fresh()->status);
    }

    public function test_maxed_out_coupon_is_rejected_and_times_used_is_not_double_incremented(): void
    {
        $subscription = $this->tenantAdmin()->institute->subscription;
        $coupon = Coupon::create([
            'code' => 'MAXED', 'discount_type' => 'flat', 'discount_value' => 50,
            'valid_from' => now()->subDay()->toDateString(), 'valid_until' => now()->addDay()->toDateString(),
            'max_uses' => 2, 'times_used' => 2,
        ]);

        $this->activateWithCoupon($subscription, 'MAXED')->assertSessionHasErrors('coupon_code');
        $this->assertSame(2, $coupon->fresh()->times_used);
    }

    public function test_inactive_coupon_is_rejected(): void
    {
        $subscription = $this->tenantAdmin()->institute->subscription;
        Coupon::create([
            'code' => 'PAUSED', 'discount_type' => 'flat', 'discount_value' => 50,
            'valid_from' => now()->subDay()->toDateString(), 'valid_until' => now()->addDay()->toDateString(),
            'is_active' => false,
        ]);

        $this->activateWithCoupon($subscription, 'PAUSED')->assertSessionHasErrors('coupon_code');
    }

    public function test_non_super_admin_gets_403_on_every_plan_coupon_and_subscription_route(): void
    {
        $admin = $this->tenantAdmin();
        $plan = Plan::first();
        $coupon = Coupon::create([
            'code' => 'GATECHECK', 'discount_type' => 'flat', 'discount_value' => 10,
            'valid_from' => now()->toDateString(), 'valid_until' => now()->addMonth()->toDateString(),
        ]);
        $subscription = $admin->institute->subscription;

        $routes = [
            ['GET', route('super-admin.plans.index')],
            ['GET', route('super-admin.plans.create')],
            ['POST', route('super-admin.plans.store')],
            ['GET', route('super-admin.plans.edit', $plan)],
            ['PUT', route('super-admin.plans.update', $plan)],
            ['DELETE', route('super-admin.plans.destroy', $plan)],
            ['PATCH', route('super-admin.plans.toggle-status', $plan)],

            ['GET', route('super-admin.coupons.index')],
            ['GET', route('super-admin.coupons.create')],
            ['POST', route('super-admin.coupons.store')],
            ['GET', route('super-admin.coupons.edit', $coupon)],
            ['PUT', route('super-admin.coupons.update', $coupon)],
            ['DELETE', route('super-admin.coupons.destroy', $coupon)],
            ['PATCH', route('super-admin.coupons.toggle-status', $coupon)],

            ['GET', route('super-admin.subscriptions.index')],
            ['GET', route('super-admin.subscriptions.edit', $subscription)],
            ['PUT', route('super-admin.subscriptions.update', $subscription)],
            ['PATCH', route('super-admin.subscriptions.cancel', $subscription)],
        ];

        foreach ($routes as [$method, $url]) {
            $this->actingAs($admin)->call($method, $url)->assertForbidden();
        }
    }

    private function activateWithCoupon($subscription, string $couponCode)
    {
        return $this->actingAs($this->superAdmin())->put(route('super-admin.subscriptions.update', $subscription), [
            'plan_id' => Plan::where('name', 'Starter')->firstOrFail()->id,
            'coupon_code' => $couponCode,
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
        ]);
    }

    private function tenantAdmin(): User
    {
        return User::where('email', 'admin@brightcoaching.com')->firstOrFail();
    }

    private function superAdmin(): User
    {
        return User::where('email', 'admin@academyflow.in')->firstOrFail();
    }
}
