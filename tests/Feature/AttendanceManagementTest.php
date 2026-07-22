<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\Concerns\CreatesUserWithActions;
use Tests\Concerns\CreatesViewOnlyUser;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use CreatesSecondInstitute, CreatesUserWithActions, CreatesViewOnlyUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_view_only_user_can_see_pages_but_gets_403_marking_attendance(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $student = $this->createStudent($instituteAdmin->tenant_id);
        $viewer = $this->createViewOnlyUser($instituteAdmin->tenant_id, 'attendance');

        $this->actingAs($viewer)->get(route('institute.attendance.index'))->assertOk();
        $this->actingAs($viewer)->get(route('institute.attendance.monthly'))->assertOk();

        $this->actingAs($viewer)
            ->post(route('institute.attendance.store'), [
                'attendance_date' => now()->toDateString(),
                'statuses' => [$student->id => 'present'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendance', ['student_id' => $student->id]);
    }

    public function test_add_only_user_can_mark_today_but_gets_403_correcting_past_date(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $student = $this->createStudent($instituteAdmin->tenant_id);
        $addOnlyUser = $this->createUserWithActions($instituteAdmin->tenant_id, 'attendance', ['view', 'add']);

        $today = $this->actingAs($addOnlyUser)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$student->id => 'present'],
        ]);
        $today->assertRedirect();
        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $past = $this->actingAs($addOnlyUser)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->subDay()->toDateString(),
            'statuses' => [$student->id => 'absent'],
        ]);
        $past->assertForbidden();
    }

    public function test_edit_only_user_can_correct_past_date_but_gets_403_marking_today(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $student = $this->createStudent($instituteAdmin->tenant_id);
        $editOnlyUser = $this->createUserWithActions($instituteAdmin->tenant_id, 'attendance', ['view', 'edit']);

        $today = $this->actingAs($editOnlyUser)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$student->id => 'present'],
        ]);
        $today->assertForbidden();

        $past = $this->actingAs($editOnlyUser)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->subDay()->toDateString(),
            'statuses' => [$student->id => 'late'],
        ]);
        $past->assertRedirect();
        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->id,
            'attendance_date' => now()->subDay()->toDateString(),
            'status' => 'late',
        ]);
    }

    public function test_marking_same_student_same_day_twice_updates_instead_of_duplicating(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $student = $this->createStudent($instituteAdmin->tenant_id);

        $this->actingAs($instituteAdmin)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$student->id => 'present'],
        ]);

        $this->actingAs($instituteAdmin)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$student->id => 'absent'],
        ]);

        $this->assertSame(1, Attendance::where('student_id', $student->id)->count());
        $this->assertSame('absent', Attendance::where('student_id', $student->id)->first()->status);
    }

    public function test_tenant_isolation_on_attendance_pages_and_marking(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $studentA = $this->createStudent($instituteAdmin->tenant_id, 'Institute A Student');

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        // Institute B's own pages must not show Institute A's student.
        $this->actingAs($adminB)->get(route('institute.attendance.index'))
            ->assertOk()
            ->assertDontSee('Institute A Student');

        // And Institute B cannot mark attendance for Institute A's student
        // by guessing their ID — Student::findOrFail() inside store() is
        // tenant-scoped, so this 404s rather than silently succeeding or
        // leaking a validation error that would confirm the ID exists.
        $response = $this->actingAs($adminB)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$studentA->id => 'present'],
        ]);
        $response->assertNotFound();

        $this->assertDatabaseMissing('attendance', ['student_id' => $studentA->id]);
    }

    public function test_monthly_summary_counts_and_percentage_are_correct(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $student = $this->createStudent($instituteAdmin->tenant_id);

        $year = (int) now()->year;
        $month = (int) now()->month;

        Attendance::mark($instituteAdmin->tenant_id, $student->id, null, sprintf('%04d-%02d-01', $year, $month), 'present', $instituteAdmin->id);
        Attendance::mark($instituteAdmin->tenant_id, $student->id, null, sprintf('%04d-%02d-02', $year, $month), 'present', $instituteAdmin->id);
        Attendance::mark($instituteAdmin->tenant_id, $student->id, null, sprintf('%04d-%02d-03', $year, $month), 'absent', $instituteAdmin->id);
        Attendance::mark($instituteAdmin->tenant_id, $student->id, null, sprintf('%04d-%02d-04', $year, $month), 'late', $instituteAdmin->id);
        Attendance::mark($instituteAdmin->tenant_id, $student->id, null, sprintf('%04d-%02d-05', $year, $month), 'leave', $instituteAdmin->id);

        $summary = Attendance::monthlySummaryFor($student->id, $year, $month);

        $this->assertSame(2, $summary['present']);
        $this->assertSame(1, $summary['absent']);
        $this->assertSame(1, $summary['late']);
        $this->assertSame(1, $summary['leave']);
        // (present + late) / total marked = (2 + 1) / 5 * 100 = 60.0
        $this->assertSame(60.0, $summary['percentage']);

        $response = $this->actingAs($instituteAdmin)->get(route('institute.attendance.monthly', [
            'ym' => sprintf('%04d-%02d', $year, $month),
            'student_id' => $student->id,
        ]));
        $response->assertOk()->assertSee('60%');
    }

    private function createStudent(int $tenantId, string $name = 'Attendance Test Student'): Student
    {
        $course = Course::create([
            'tenant_id' => $tenantId,
            'name' => 'Attendance Test Course',
            'duration' => '1 month',
            'fee' => 500,
        ]);

        return Student::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'parent_mobile' => '9990001122',
            'course_id' => $course->id,
        ]);
    }
}
