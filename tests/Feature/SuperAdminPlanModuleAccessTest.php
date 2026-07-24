<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanModule;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPlanModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_plans_page_renders_a_checkbox_grid_reflecting_current_access(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();

        $response = $this->actingAs($superAdmin)->get(route('super-admin.plans.index'));

        $response->assertOk()
            ->assertSee('Module Access by Plan')
            ->assertSee('WhatsApp Reminders')
            ->assertSee('Professional');
    }

    public function test_submitting_the_grid_adds_and_removes_module_access_in_one_request(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();

        // Professional starts with whatsapp_reminder + sms (seeded). Flip it:
        // give Starter qr_attendance, and drop sms from Professional.
        $response = $this->actingAs($superAdmin)->patch(route('super-admin.plans.module-access.update'), [
            'modules' => [
                $starter->id => ['qr_attendance'],
                $professional->id => ['whatsapp_reminder'],
            ],
        ]);

        $response->assertRedirect(route('super-admin.plans.index'));

        $this->assertTrue(PlanModule::where('plan_id', $starter->id)->where('module_key', 'qr_attendance')->exists());
        $this->assertTrue(PlanModule::where('plan_id', $professional->id)->where('module_key', 'whatsapp_reminder')->exists());
        $this->assertFalse(PlanModule::where('plan_id', $professional->id)->where('module_key', 'sms')->exists());
    }

    public function test_submitting_an_invalid_module_key_is_silently_ignored(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();

        $this->actingAs($superAdmin)->patch(route('super-admin.plans.module-access.update'), [
            'modules' => [$starter->id => ['not_a_real_module']],
        ]);

        $this->assertFalse(PlanModule::where('plan_id', $starter->id)->exists());
    }

    public function test_non_super_admin_gets_403_on_module_access_update(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('super-admin.plans.module-access.update'), ['modules' => []])
            ->assertForbidden();
    }
}
