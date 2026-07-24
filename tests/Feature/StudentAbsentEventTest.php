<?php

namespace Tests\Feature;

use App\Events\StudentAbsentEvent;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StudentAbsentEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function makeStudent(int $tenantId): Student
    {
        $course = Course::create(['tenant_id' => $tenantId, 'name' => 'Test Course', 'duration' => '1 month', 'fee' => 500]);

        return Student::create([
            'tenant_id' => $tenantId, 'name' => 'Absent Test Student',
            'parent_mobile' => '9999900001', 'course_id' => $course->id,
        ]);
    }

    public function test_marking_a_student_absent_for_the_first_time_dispatches_the_event(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $student = $this->makeStudent($admin->tenant_id);

        Event::fake([StudentAbsentEvent::class]);

        $this->actingAs($admin)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$student->id => 'absent'],
        ]);

        Event::assertDispatched(StudentAbsentEvent::class, fn ($event) => $event->student->id === $student->id);
    }

    public function test_marking_an_already_absent_student_absent_again_does_not_redispatch(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $student = $this->makeStudent($admin->tenant_id);
        $date = now()->toDateString();

        // First submission - genuinely new absence.
        $this->actingAs($admin)->post(route('institute.attendance.store'), [
            'attendance_date' => $date,
            'statuses' => [$student->id => 'absent'],
        ]);

        // Re-saving the same day's form, unchanged (e.g. staff reopens and clicks Save again).
        Event::fake([StudentAbsentEvent::class]);

        $this->actingAs($admin)->post(route('institute.attendance.store'), [
            'attendance_date' => $date,
            'statuses' => [$student->id => 'absent'],
        ]);

        Event::assertNotDispatched(StudentAbsentEvent::class);
    }

    public function test_changing_a_present_student_to_absent_dispatches_the_event(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $student = $this->makeStudent($admin->tenant_id);
        $date = now()->toDateString();

        $this->actingAs($admin)->post(route('institute.attendance.store'), [
            'attendance_date' => $date,
            'statuses' => [$student->id => 'present'],
        ]);

        Event::fake([StudentAbsentEvent::class]);

        $this->actingAs($admin)->post(route('institute.attendance.store'), [
            'attendance_date' => $date,
            'statuses' => [$student->id => 'absent'],
        ]);

        Event::assertDispatched(StudentAbsentEvent::class);
    }

    public function test_marking_a_student_present_never_dispatches(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $student = $this->makeStudent($admin->tenant_id);

        Event::fake([StudentAbsentEvent::class]);

        $this->actingAs($admin)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$student->id => 'present'],
        ]);

        Event::assertNotDispatched(StudentAbsentEvent::class);
    }
}
