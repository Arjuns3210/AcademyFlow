<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Plan;
use App\Models\Student;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * One continuous narrative across every Sprint 9 piece together, the same
 * spirit as Sprint 8A's FullLifecycleTest — each piece (ModuleAccessService,
 * WhatsAppService, the two listeners, the manual buttons, the settings page,
 * the sidebar) already has its own dedicated test file; this file's value is
 * proving they compose correctly across a real plan change, not testing any
 * one of them in isolation again.
 */
class Sprint9FullLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_a_starter_institute_is_blocked_everywhere_then_professional_unlocks_everything(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);

        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Lifecycle Course', 'duration' => '1 month', 'fee' => 1000]);
        $student = Student::create([
            'tenant_id' => $admin->tenant_id, 'name' => 'Lifecycle Student',
            'parent_name' => 'Lifecycle Parent', 'parent_mobile' => '9888800001', 'course_id' => $course->id,
        ]);
        WhatsAppSetting::create(['institute_id' => null, 'phone_number_id' => 'LC1', 'access_token' => 'tok', 'is_active' => true]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.LIFECYCLE']]], 200)]);

        // --- Starter: blocked at every layer ---
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee('WhatsApp');
        $this->actingAs($admin)->get(route('institute.whatsapp.index'))
            ->assertRedirect(route('institute.feature-locked', ['moduleKey' => 'whatsapp_reminder']));
        $this->actingAs($admin)->get(route('institute.fees.show', $student->fee))->assertDontSee('Send Reminder');
        $this->actingAs($admin)->post(route('institute.fees.send-reminder', $student->fee))
            ->assertRedirect(route('institute.feature-locked', ['moduleKey' => 'whatsapp_reminder']));

        $this->actingAs($admin)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$student->id => 'absent'],
        ]);
        Http::assertNothingSent();
        $this->assertSame(0, WhatsAppLog::count());

        // --- Super Admin upgrades the institute to Professional ---
        $admin->institute->subscription->update(['plan_id' => $professional->id]);

        // --- Now: unlocked everywhere, and a real (mocked) send goes through ---
        $this->actingAs($admin)->get(route('dashboard'))->assertSee('WhatsApp');

        $this->actingAs($admin)->post(route('institute.fees.send-reminder', $student->fee), ['due_date_label' => '1st of next month']);

        $log = WhatsAppLog::firstOrFail();
        $this->assertSame('sent', $log->status);
        $this->assertSame($student->id, $log->student_id);

        $this->actingAs($admin)->get(route('institute.whatsapp.index'))
            ->assertOk()
            ->assertSee('Connected (Demo)')
            ->assertSee($student->parent_mobile);
    }
}
