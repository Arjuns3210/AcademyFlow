<?php

namespace Tests\Feature;

use App\Events\StudentAbsentEvent;
use App\Listeners\SendStudentAbsentWhatsAppReminder;
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
 * Tests the listener's own logic directly (constructing it and calling
 * handle()) rather than through the real queue - QUEUE_CONNECTION=database
 * here, so an actual dispatch would just sit in the jobs table until a
 * worker processes it. The queue mechanism itself is Laravel's, not
 * something this app needs to re-test; what matters is this listener's
 * access-check + skip + send logic, which direct invocation exercises
 * identically to how a worker eventually would.
 */
class SendStudentAbsentWhatsAppReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function makeStudentWithParentMobile(int $tenantId, string $parentMobile = '9999900002'): Student
    {
        $course = Course::create(['tenant_id' => $tenantId, 'name' => 'Test Course', 'duration' => '1 month', 'fee' => 500]);

        return Student::create([
            'tenant_id' => $tenantId, 'name' => 'Reminder Test Student', 'parent_name' => 'Test Parent',
            'parent_mobile' => $parentMobile, 'course_id' => $course->id,
        ]);
    }

    public function test_sends_and_logs_when_the_institutes_plan_includes_the_module(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);
        WhatsAppSetting::create(['institute_id' => $admin->institute->id, 'phone_number_id' => '123', 'access_token' => 'tok', 'is_active' => true]);
        $student = $this->makeStudentWithParentMobile($admin->tenant_id);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABS1']]], 200)]);

        app(SendStudentAbsentWhatsAppReminder::class)->handle(
            new StudentAbsentEvent($admin->institute, $student, now()->toDateString())
        );

        Http::assertSent(fn ($request) => str_contains($request->url(), '123'));
        $log = WhatsAppLog::firstOrFail();
        $this->assertSame('sent', $log->status);
        $this->assertSame('attendance_absent', $log->template_name);
        $this->assertSame($student->id, $log->student_id);
    }

    public function test_starter_plan_institute_is_silently_skipped_without_calling_the_api(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);
        WhatsAppSetting::create(['institute_id' => $admin->institute->id, 'phone_number_id' => '123', 'access_token' => 'tok', 'is_active' => true]);
        $student = $this->makeStudentWithParentMobile($admin->tenant_id);

        Http::fake();

        app(SendStudentAbsentWhatsAppReminder::class)->handle(
            new StudentAbsentEvent($admin->institute, $student, now()->toDateString())
        );

        Http::assertNothingSent();
        $this->assertSame(0, WhatsAppLog::count());
    }

    public function test_student_with_a_blank_parent_mobile_is_skipped_even_with_full_access(): void
    {
        // parent_mobile is a required (NOT NULL) column - an empty string is
        // the only way this state can exist, e.g. bad legacy/import data.
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $enterprise = Plan::where('name', 'Enterprise')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $enterprise->id]);
        WhatsAppSetting::create(['institute_id' => $admin->institute->id, 'phone_number_id' => '123', 'access_token' => 'tok', 'is_active' => true]);
        $student = $this->makeStudentWithParentMobile($admin->tenant_id, '');

        Http::fake();

        app(SendStudentAbsentWhatsAppReminder::class)->handle(
            new StudentAbsentEvent($admin->institute, $student, now()->toDateString())
        );

        Http::assertNothingSent();
        $this->assertSame(0, WhatsAppLog::count());
    }

    public function test_trial_institute_gets_full_access_and_sends(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'trial', 'plan_id' => null, 'trial_ends_at' => now()->addDays(3)]);
        WhatsAppSetting::create(['institute_id' => null, 'phone_number_id' => 'PLATFORM', 'access_token' => 'tok', 'is_active' => true]);
        $student = $this->makeStudentWithParentMobile($admin->tenant_id);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABS2']]], 200)]);

        app(SendStudentAbsentWhatsAppReminder::class)->handle(
            new StudentAbsentEvent($admin->institute, $student, now()->toDateString())
        );

        $this->assertSame('sent', WhatsAppLog::firstOrFail()->status);
    }
}
