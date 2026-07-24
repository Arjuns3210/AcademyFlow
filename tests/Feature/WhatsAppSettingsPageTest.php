<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\TestCase;

class WhatsAppSettingsPageTest extends TestCase
{
    use CreatesSecondInstitute, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function giveAccess(User $admin): void
    {
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);
    }

    public function test_shows_connected_own_when_the_institute_has_its_own_active_settings(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $this->giveAccess($admin);
        WhatsAppSetting::create(['institute_id' => $admin->institute->id, 'phone_number_id' => '1', 'access_token' => 'x', 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('institute.whatsapp.index'));

        $response->assertOk()->assertSee('Connected')->assertDontSee('Connected (Demo)')->assertDontSee('Not Connected');
    }

    public function test_shows_connected_demo_when_falling_back_to_the_platform_default(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $this->giveAccess($admin);
        WhatsAppSetting::create(['institute_id' => null, 'phone_number_id' => '1', 'access_token' => 'x', 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('institute.whatsapp.index'));

        $response->assertOk()->assertSee('Connected (Demo)');
    }

    public function test_shows_not_connected_when_nothing_is_configured(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $this->giveAccess($admin);

        $response = $this->actingAs($admin)->get(route('institute.whatsapp.index'));

        $response->assertOk()->assertSee('Not Connected');
    }

    public function test_lists_this_institutes_reminder_logs_with_sr_no_and_pagination(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $this->giveAccess($admin);

        for ($i = 1; $i <= 25; $i++) {
            WhatsAppLog::create([
                'institute_id' => $admin->institute->id, 'template_name' => 'fee_reminder',
                'recipient_number' => "90000000{$i}", 'status' => 'sent',
            ]);
        }

        $page1 = $this->actingAs($admin)->get(route('institute.whatsapp.index'));
        $page1->assertOk()->assertSee('Sr No')->assertViewHas('logs', fn ($logs) => $logs->firstItem() === 1);

        $page2 = $this->actingAs($admin)->get(route('institute.whatsapp.index', ['page' => 2]));
        $page2->assertOk()->assertViewHas('logs', fn ($logs) => $logs->firstItem() === 21);
    }

    public function test_whatsapp_logs_are_scoped_to_the_current_institute_only(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $this->giveAccess($admin);
        WhatsAppLog::create([
            'institute_id' => $admin->institute->id, 'template_name' => 'fee_reminder',
            'recipient_number' => '9000000001', 'status' => 'sent',
        ]);

        [$instituteB, $adminB] = $this->createSecondInstituteWithAdmin();
        $this->giveAccess($adminB);
        WhatsAppLog::create([
            'institute_id' => $instituteB->id, 'template_name' => 'attendance_absent',
            'recipient_number' => '9000000002', 'status' => 'sent',
        ]);

        $responseB = $this->actingAs($adminB)->get(route('institute.whatsapp.index'));

        $responseB->assertOk()->assertSee('9000000002')->assertDontSee('9000000001');
    }

    public function test_page_is_blocked_for_a_starter_plan_institute(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $response = $this->actingAs($admin)->get(route('institute.whatsapp.index'));

        $response->assertRedirect(route('institute.feature-locked', ['moduleKey' => 'whatsapp_reminder']));
    }

    public function test_sidebar_link_only_appears_when_module_access_is_granted(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee('WhatsApp');

        $this->giveAccess($admin);
        $this->actingAs($admin)->get(route('dashboard'))->assertSee('WhatsApp');
    }
}
