<?php

namespace Tests\Feature;

use App\Events\FeeDueEvent;
use App\Listeners\SendFeeDueWhatsAppReminder;
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

class SendFeeDueWhatsAppReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function makeStudentWithFee(int $tenantId, ?string $parentMobile = '9999900003'): Student
    {
        $course = Course::create(['tenant_id' => $tenantId, 'name' => 'Test Course', 'duration' => '1 month', 'fee' => 1500]);

        return Student::create([
            'tenant_id' => $tenantId, 'name' => 'Fee Reminder Test Student', 'parent_name' => 'Test Parent',
            'parent_mobile' => $parentMobile, 'course_id' => $course->id,
        ]);
    }

    public function test_sends_and_logs_when_the_institutes_plan_includes_the_module(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);
        WhatsAppSetting::create(['institute_id' => $admin->institute->id, 'phone_number_id' => '456', 'access_token' => 'tok', 'is_active' => true]);
        $student = $this->makeStudentWithFee($admin->tenant_id);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.FEE1']]], 200)]);

        app(SendFeeDueWhatsAppReminder::class)->handle(
            new FeeDueEvent($admin->institute, $student->fee, 'the 5th')
        );

        $log = WhatsAppLog::firstOrFail();
        $this->assertSame('sent', $log->status);
        $this->assertSame('fee_reminder', $log->template_name);

        Http::assertSent(fn ($request) => $request['template']['components'][0]['parameters'][4]['text'] === 'the 5th');
    }

    public function test_missing_due_date_label_falls_back_to_a_generic_phrase(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);
        WhatsAppSetting::create(['institute_id' => $admin->institute->id, 'phone_number_id' => '456', 'access_token' => 'tok', 'is_active' => true]);
        $student = $this->makeStudentWithFee($admin->tenant_id);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.FEE2']]], 200)]);

        app(SendFeeDueWhatsAppReminder::class)->handle(new FeeDueEvent($admin->institute, $student->fee));

        Http::assertSent(fn ($request) => $request['template']['components'][0]['parameters'][4]['text'] === 'at your earliest convenience');
    }

    public function test_starter_plan_institute_is_silently_skipped(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);
        WhatsAppSetting::create(['institute_id' => $admin->institute->id, 'phone_number_id' => '456', 'access_token' => 'tok', 'is_active' => true]);
        $student = $this->makeStudentWithFee($admin->tenant_id);

        Http::fake();

        app(SendFeeDueWhatsAppReminder::class)->handle(new FeeDueEvent($admin->institute, $student->fee));

        Http::assertNothingSent();
        $this->assertSame(0, WhatsAppLog::count());
    }
}
