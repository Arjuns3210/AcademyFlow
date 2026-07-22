<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FeeTransaction;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 8C's soft-delete spot check. Students/courses/teachers/batches
 * each already have dedicated "soft-deleted X is hidden from Y" tests from
 * their own sprints (still passing as part of the full suite) — this file
 * covers the one gap those wouldn't catch: a StudentFee/FeeTransaction row
 * survives its student being soft-deleted (soft-delete doesn't cascade),
 * and the Fee/Collection Reports must still show that student's name
 * rather than silently rendering blank.
 */
class SoftDeleteDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_fee_report_shows_a_soft_deleted_students_name_instead_of_going_blank(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Trashed Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Since Deleted Student', 'parent_mobile' => '9999999997', 'course_id' => $course->id]);

        $student->delete();
        $this->assertSoftDeleted($student);

        $response = $this->actingAs($admin)->get(route('institute.reports.fees'));

        $response->assertOk()->assertSee('Since Deleted Student');
    }

    public function test_collection_report_shows_a_soft_deleted_students_name_instead_of_going_blank(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Trashed Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Paid Then Deleted Student', 'parent_mobile' => '9999999996', 'course_id' => $course->id]);
        FeeTransaction::create([
            'tenant_id' => $admin->tenant_id, 'student_fee_id' => $student->fee->id,
            'amount' => 100, 'payment_mode' => 'cash', 'collected_by' => $admin->id,
        ]);

        $student->delete();

        $response = $this->actingAs($admin)->get(route('institute.reports.collections'));

        $response->assertOk()->assertSee('Paid Then Deleted Student');
    }

    /**
     * Found live: the Fee Overview module (FeeController, not ReportController)
     * had the exact same gap the Reports already got fixed for in 8C — nobody
     * had checked the sibling controller. A soft-deleted student's fee row
     * crashed the whole /institute/fees list with "Attempt to read property
     * name on null" instead of just rendering. Covers the fee list, the fee
     * detail page, the PDF receipt, and the discount-update activity log —
     * every place FeeController touches ->student.
     */
    public function test_fee_overview_list_shows_a_soft_deleted_students_name_instead_of_crashing(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Trashed Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Overview Deleted Student', 'parent_mobile' => '9999999995', 'course_id' => $course->id]);

        $student->delete();
        $this->assertSoftDeleted($student);

        $response = $this->actingAs($admin)->get(route('institute.fees.index'));

        $response->assertOk()->assertSee('Overview Deleted Student');
    }

    public function test_fee_detail_page_shows_a_soft_deleted_students_name_instead_of_crashing(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Trashed Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Detail Deleted Student', 'parent_mobile' => '9999999994', 'course_id' => $course->id]);
        $fee = $student->fee;

        $student->delete();

        $response = $this->actingAs($admin)->get(route('institute.fees.show', $fee));

        $response->assertOk()->assertSee('Detail Deleted Student');
    }

    public function test_receipt_pdf_does_not_crash_for_a_soft_deleted_students_transaction(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Trashed Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Receipt Deleted Student', 'parent_mobile' => '9999999993', 'course_id' => $course->id]);
        $transaction = FeeTransaction::create([
            'tenant_id' => $admin->tenant_id, 'student_fee_id' => $student->fee->id,
            'amount' => 100, 'payment_mode' => 'cash', 'collected_by' => $admin->id,
        ]);

        $student->delete();

        $response = $this->actingAs($admin)->get(route('institute.fees.transactions.receipt', $transaction));

        $response->assertOk();
    }

    public function test_updating_discount_for_a_soft_deleted_students_fee_does_not_crash(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Trashed Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Discount Deleted Student', 'parent_mobile' => '9999999992', 'course_id' => $course->id]);
        $fee = $student->fee;

        $student->delete();

        $response = $this->actingAs($admin)->patch(route('institute.fees.discount.update', $fee), [
            'discount_amount' => 50,
            'discount_reason' => 'Loyalty discount',
        ]);

        $response->assertRedirect(route('institute.fees.show', $fee));
        $this->assertDatabaseHas('activity_logs', ['action' => 'discount_updated']);
    }
}
