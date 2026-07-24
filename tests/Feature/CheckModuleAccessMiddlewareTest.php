<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * No real module-gated route exists yet (that lands in 9D with the WhatsApp
 * Settings page) - a throwaway route registered here exercises the actual
 * middleware pipeline end-to-end rather than constructing Request objects
 * by hand, which is the same technique used to test any other middleware
 * in isolation.
 */
class CheckModuleAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        Route::middleware(['web', 'auth', 'module:whatsapp_reminder'])
            ->get('/__test-module-gate', fn () => 'GRANTED');
    }

    public function test_denied_access_redirects_to_the_feature_locked_page(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $response = $this->actingAs($admin)->get('/__test-module-gate');

        $response->assertRedirect(route('institute.feature-locked', ['moduleKey' => 'whatsapp_reminder']));
    }

    public function test_granted_access_passes_through_to_the_route(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);

        $response = $this->actingAs($admin)->get('/__test-module-gate');

        $response->assertOk()->assertSee('GRANTED');
    }

    public function test_trial_institute_passes_through_regardless_of_plan(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $admin->institute->subscription->update([
            'status' => 'trial', 'plan_id' => null, 'trial_ends_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($admin)->get('/__test-module-gate');

        $response->assertOk()->assertSee('GRANTED');
    }
}
