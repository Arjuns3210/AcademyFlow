<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureLockedPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_feature_locked_page_names_the_plans_that_unlock_the_module(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('institute.feature-locked', ['moduleKey' => 'whatsapp_reminder']));

        // Seeded onto both Professional and Enterprise.
        $response->assertOk()
            ->assertSee('WhatsApp Reminders')
            ->assertSee('Professional')
            ->assertSee('Enterprise');
    }

    public function test_feature_locked_page_handles_a_module_nobody_unlocks_yet(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('institute.feature-locked', ['moduleKey' => 'made_up_key']));

        $response->assertOk()->assertSee('a higher plan');
    }
}
