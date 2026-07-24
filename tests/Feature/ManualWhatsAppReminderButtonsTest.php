<?php

namespace Tests\Feature;

use App\Events\FeeDueEvent;
use App\Events\StudentAbsentEvent;
use App\Models\Course;
use App\Models\Plan;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ManualWhatsAppReminderButtonsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function makeStudent(int $tenantId): Student
    {
        $course = Course::create(['tenant_id' => $tenantId, 'name' => 'Test Course', 'duration' => '1 month', 'fee' => 1000]);

        return Student::create([
            'tenant_id' => $tenantId, 'name' => 'Manual Reminder Student',
            'parent_mobile' => '9999900004', 'course_id' => $course->id,
        ]);
    }

    public function test_manual_fee_reminder_dispatches_fee_due_event_with_the_typed_due_date(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);
        $student = $this->makeStudent($admin->tenant_id);

        Event::fake([FeeDueEvent::class]);

        $response = $this->actingAs($admin)->post(route('institute.fees.send-reminder', $student->fee), [
            'due_date_label' => '5th August',
        ]);

        $response->assertRedirect(route('institute.fees.show', $student->fee));
        Event::assertDispatched(FeeDueEvent::class, fn ($event) => $event->studentFee->id === $student->fee->id
            && $event->dueDateLabel === '5th August');
    }

    public function test_manual_fee_reminder_is_blocked_for_a_starter_plan_institute(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);
        $student = $this->makeStudent($admin->tenant_id);

        Event::fake([FeeDueEvent::class]);

        $response = $this->actingAs($admin)->post(route('institute.fees.send-reminder', $student->fee), []);

        $response->assertRedirect(route('institute.feature-locked', ['moduleKey' => 'whatsapp_reminder']));
        Event::assertNotDispatched(FeeDueEvent::class);
    }

    public function test_manual_attendance_reminder_dispatches_student_absent_event_for_the_viewed_date(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);
        $student = $this->makeStudent($admin->tenant_id);
        $date = now()->subDay()->toDateString();

        Event::fake([StudentAbsentEvent::class]);

        $response = $this->actingAs($admin)->post(route('institute.attendance.send-reminder', $student), [
            'attendance_date' => $date,
        ]);

        $response->assertRedirect(route('institute.attendance.index', ['date' => $date]));
        Event::assertDispatched(StudentAbsentEvent::class, fn ($event) => $event->student->id === $student->id
            && $event->attendanceDate === $date);
    }

    public function test_manual_attendance_reminder_is_blocked_for_a_starter_plan_institute(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $starter = Plan::where('name', 'Starter')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $starter->id]);
        $student = $this->makeStudent($admin->tenant_id);

        Event::fake([StudentAbsentEvent::class]);

        $response = $this->actingAs($admin)->post(route('institute.attendance.send-reminder', $student), [
            'attendance_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('institute.feature-locked', ['moduleKey' => 'whatsapp_reminder']));
        Event::assertNotDispatched(StudentAbsentEvent::class);
    }
}
