<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeeTransaction;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\Concerns\CreatesViewOnlyUser;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use CreatesSecondInstitute, CreatesViewOnlyUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_report_filters_by_course_and_export_respects_the_same_filter(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;

        $courseA = $this->createCourse($tenantId, 'Course A');
        $courseB = $this->createCourse($tenantId, 'Course B');
        $this->createStudent($tenantId, $courseA->id, name: 'Student In A');
        $this->createStudent($tenantId, $courseB->id, name: 'Student In B');

        $response = $this->actingAs($admin)->get(route('institute.reports.students', ['course_id' => $courseA->id]));
        $response->assertOk();
        $response->assertSee('Student In A');
        $response->assertDontSee('Student In B');

        $csv = $this->actingAs($admin)->get(route('institute.reports.students.export', ['course_id' => $courseA->id]));
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Student In A', $content);
        $this->assertStringNotContainsString('Student In B', $content);
    }

    public function test_student_report_export_covers_every_matching_row_not_just_the_current_page(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;
        $course = $this->createCourse($tenantId, 'Bulk Course');

        for ($i = 1; $i <= 25; $i++) {
            $this->createStudent($tenantId, $course->id, name: "Bulk Student {$i}");
        }

        $onScreen = $this->actingAs($admin)->get(route('institute.reports.students'));
        $onScreen->assertOk();
        $onScreen->assertViewHas('students', fn ($students) => $students->total() === 25 && $students->count() === 20);

        $csv = $this->actingAs($admin)->get(route('institute.reports.students.export'));
        $csv->assertOk();
        $lines = array_values(array_filter(explode("\n", trim($csv->streamedContent()))));
        $this->assertCount(26, $lines); // 1 header row + 25 data rows, not capped at the 20-per-page screen size
    }

    public function test_attendance_report_summary_filters_by_batch(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;
        $course = $this->createCourse($tenantId);
        $batchA = $this->createBatch($tenantId, $course->id, 'Batch A');
        $batchB = $this->createBatch($tenantId, $course->id, 'Batch B');
        $studentA = $this->createStudent($tenantId, $course->id, $batchA->id, 'In Batch A');
        $studentB = $this->createStudent($tenantId, $course->id, $batchB->id, 'In Batch B');

        $today = now()->toDateString();
        Attendance::mark($tenantId, $studentA->id, $batchA->id, $today, 'present', $admin->id);
        Attendance::mark($tenantId, $studentB->id, $batchB->id, $today, 'present', $admin->id);

        $response = $this->actingAs($admin)->get(route('institute.reports.attendance', [
            'from' => $today,
            'to' => $today,
            'batch_id' => $batchA->id,
            'mode' => 'summary',
        ]));

        // Not assertDontSee('In Batch B') here — the Student filter <select>
        // legitimately lists every active student regardless of the batch
        // filter, so that name is on the page either way. What must actually
        // be scoped to Batch A is the summary table's own data.
        $response->assertOk();
        $response->assertSee('In Batch A');
        $response->assertViewHas(
            'summaryRows',
            fn ($rows) => $rows->count() === 1 && $rows->first()['student']->id === $studentA->id
        );

        $csv = $this->actingAs($admin)->get(route('institute.reports.attendance.export', [
            'from' => $today,
            'to' => $today,
            'batch_id' => $batchA->id,
            'mode' => 'summary',
        ]));
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringContainsString('In Batch A', $content);
        $this->assertStringNotContainsString('In Batch B', $content);
    }

    public function test_fee_report_filters_by_status_and_export_respects_the_same_filter(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;
        $course = $this->createCourse($tenantId, 'Fee Course');

        $paidStudent = $this->createStudent($tenantId, $course->id, name: 'Paid Student');
        $this->createStudent($tenantId, $course->id, name: 'Pending Student');

        FeeTransaction::create([
            'tenant_id' => $tenantId,
            'student_fee_id' => $paidStudent->fee->id,
            'amount' => $paidStudent->fee->net_fee,
            'payment_mode' => 'cash',
            'collected_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('institute.reports.fees', ['status' => 'paid']));
        $response->assertOk();
        $response->assertSee('Paid Student');
        $response->assertDontSee('Pending Student');

        $csv = $this->actingAs($admin)->get(route('institute.reports.fees.export', ['status' => 'paid']));
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Paid Student', $content);
        $this->assertStringNotContainsString('Pending Student', $content);
    }

    public function test_fee_report_export_covers_every_matching_row_not_just_the_current_page(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;
        $course = $this->createCourse($tenantId, 'Bulk Fee Course');

        for ($i = 1; $i <= 25; $i++) {
            $this->createStudent($tenantId, $course->id, name: "Fee Bulk {$i}");
        }

        $onScreen = $this->actingAs($admin)->get(route('institute.reports.fees'));
        $onScreen->assertOk();
        $onScreen->assertViewHas('fees', fn ($fees) => $fees->total() === 25 && $fees->count() === 20);

        $csv = $this->actingAs($admin)->get(route('institute.reports.fees.export'));
        $csv->assertOk();
        $lines = array_values(array_filter(explode("\n", trim($csv->streamedContent()))));
        $this->assertCount(26, $lines);
    }

    public function test_collection_report_filters_by_payment_mode_and_total_reflects_the_filtered_set(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;
        $course = $this->createCourse($tenantId);
        $student = $this->createStudent($tenantId, $course->id);

        $cash = FeeTransaction::create([
            'tenant_id' => $tenantId,
            'student_fee_id' => $student->fee->id,
            'amount' => 100,
            'payment_mode' => 'cash',
            'collected_by' => $admin->id,
        ]);
        $upi = FeeTransaction::create([
            'tenant_id' => $tenantId,
            'student_fee_id' => $student->fee->id,
            'amount' => 200,
            'payment_mode' => 'upi',
            'collected_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('institute.reports.collections', ['payment_mode' => 'upi']));
        $response->assertOk();
        $response->assertSee($upi->receipt_number);
        $response->assertDontSee($cash->receipt_number);
        $response->assertViewHas('totalCollected', 200.0);

        $csv = $this->actingAs($admin)->get(route('institute.reports.collections.export', ['payment_mode' => 'upi']));
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringContainsString($upi->receipt_number, $content);
        $this->assertStringNotContainsString($cash->receipt_number, $content);
    }

    public function test_user_without_reports_view_gets_403_on_all_report_routes(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;
        $viewer = $this->createViewOnlyUser($tenantId, 'students');

        $routes = [
            ['institute.reports.index', []],
            ['institute.reports.students', []],
            ['institute.reports.students.export', []],
            ['institute.reports.students.export-pdf', []],
            ['institute.reports.attendance', []],
            ['institute.reports.attendance.export', ['mode' => 'summary']],
            ['institute.reports.attendance.export-pdf', ['mode' => 'summary']],
            ['institute.reports.fees', []],
            ['institute.reports.fees.export', []],
            ['institute.reports.fees.export-pdf', []],
            ['institute.reports.collections', []],
            ['institute.reports.collections.export', []],
            ['institute.reports.collections.export-pdf', []],
        ];

        foreach ($routes as [$name, $params]) {
            $this->actingAs($viewer)->get(route($name, $params))->assertForbidden();
        }
    }

    public function test_tenant_isolation_across_all_four_reports(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;
        $course = $this->createCourse($tenantId, 'Tenant A Course');
        $student = $this->createStudent($tenantId, $course->id, name: 'Tenant A Student');

        $today = now()->toDateString();
        Attendance::mark($tenantId, $student->id, null, $today, 'present', $admin->id);
        FeeTransaction::create([
            'tenant_id' => $tenantId,
            'student_fee_id' => $student->fee->id,
            'amount' => 100,
            'payment_mode' => 'cash',
            'collected_by' => $admin->id,
        ]);

        [$instituteB, $adminB] = $this->createSecondInstituteWithAdmin();
        $courseB = Course::create(['tenant_id' => $instituteB->id, 'name' => 'Tenant B Course', 'duration' => '1 month', 'fee' => 500]);
        $studentB = Student::create([
            'tenant_id' => $instituteB->id,
            'name' => 'Tenant B Student',
            'parent_mobile' => '8880000000',
            'course_id' => $courseB->id,
            'status' => 'active',
        ]);
        Attendance::mark($instituteB->id, $studentB->id, null, $today, 'present', $adminB->id);
        FeeTransaction::create([
            'tenant_id' => $instituteB->id,
            'student_fee_id' => $studentB->fee->id,
            'amount' => 200,
            'payment_mode' => 'cash',
            'collected_by' => $adminB->id,
        ]);

        $this->actingAs($admin)->get(route('institute.reports.students'))
            ->assertOk()->assertSee('Tenant A Student')->assertDontSee('Tenant B Student');

        $this->actingAs($admin)->get(route('institute.reports.attendance', ['from' => $today, 'to' => $today]))
            ->assertOk()->assertSee('Tenant A Student')->assertDontSee('Tenant B Student');

        $this->actingAs($admin)->get(route('institute.reports.fees'))
            ->assertOk()->assertSee('Tenant A Student')->assertDontSee('Tenant B Student');

        $this->actingAs($admin)->get(route('institute.reports.collections'))
            ->assertOk()->assertDontSee('Tenant B Student');
    }

    private function createCourse(int $tenantId, string $name = 'Report Test Course'): Course
    {
        return Course::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'duration' => '1 month',
            'fee' => 1000,
        ]);
    }

    private function createBatch(int $tenantId, int $courseId, string $name = 'Report Test Batch'): Batch
    {
        return Batch::create([
            'tenant_id' => $tenantId,
            'course_id' => $courseId,
            'name' => $name,
            'time_slot' => '5 PM - 6 PM',
        ]);
    }

    private function createStudent(int $tenantId, int $courseId, ?int $batchId = null, string $name = 'Report Test Student'): Student
    {
        return Student::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'parent_mobile' => '9990000000',
            'course_id' => $courseId,
            'batch_id' => $batchId,
            'status' => 'active',
        ]);
    }
}
