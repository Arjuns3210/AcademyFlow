<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeeTransaction;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\WhatsAppLog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\TestCase;

/**
 * A single consolidated pass across every major resource type, rather than
 * relying on the isolation coverage scattered one-per-module across each
 * sprint's own test file. Most of the individual checks below already exist
 * somewhere (StudentManagementTest, TeacherManagementTest, etc.) — this
 * file's value is having all 8 confirmed together in one place, plus the
 * one genuinely new angle no single sprint would have thought to test: a
 * cross-tenant foreign-key injection, where Institute B tries to create a
 * record that *points at* Institute A's data via a submitted ID rather than
 * trying to read/write Institute A's record directly.
 */
class TenantIsolationSweepTest extends TestCase
{
    use CreatesSecondInstitute, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_students_are_tenant_isolated(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Isolation Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Student', 'parent_mobile' => '9990001111', 'course_id' => $course->id]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.students.show', $student))->assertNotFound();
        $this->actingAs($adminB)->put(route('institute.students.update', $student), ['name' => 'Hijacked', 'parent_mobile' => '9990001111'])->assertNotFound();
        $this->actingAs($adminB)->delete(route('institute.students.destroy', $student))->assertNotFound();
        $this->actingAs($adminB)->get(route('institute.students.index'))->assertOk()->assertDontSee('Institute A Student');
        $this->assertNotNull(Student::withoutGlobalScopes()->find($student->id));
    }

    public function test_teachers_are_tenant_isolated(): void
    {
        $admin = $this->tenantAdmin();
        $teacher = Teacher::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Teacher', 'mobile' => '9990002222']);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.teachers.edit', $teacher))->assertNotFound();
        $this->actingAs($adminB)->put(route('institute.teachers.update', $teacher), ['name' => 'Hijacked', 'mobile' => '9990002222'])->assertNotFound();
        $this->actingAs($adminB)->delete(route('institute.teachers.destroy', $teacher))->assertNotFound();
        $this->assertNotNull(Teacher::withoutGlobalScopes()->find($teacher->id));
    }

    public function test_courses_are_tenant_isolated(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Course', 'duration' => '1 month', 'fee' => 500]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.courses.edit', $course))->assertNotFound();
        $this->actingAs($adminB)->put(route('institute.courses.update', $course), ['name' => 'Hijacked', 'duration' => '1 month', 'fee' => 500])->assertNotFound();
        $this->actingAs($adminB)->delete(route('institute.courses.destroy', $course))->assertNotFound();
        $this->assertNotNull(Course::withoutGlobalScopes()->find($course->id));
    }

    public function test_batches_are_tenant_isolated(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Course', 'duration' => '1 month', 'fee' => 500]);
        $batch = Batch::create(['tenant_id' => $admin->tenant_id, 'course_id' => $course->id, 'name' => 'Institute A Batch', 'time_slot' => '5 PM - 6 PM']);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.batches.edit', $batch))->assertNotFound();
        $this->actingAs($adminB)->put(route('institute.batches.update', $batch), ['course_id' => $course->id, 'name' => 'Hijacked', 'time_slot' => '5 PM - 6 PM'])->assertNotFound();
        $this->actingAs($adminB)->delete(route('institute.batches.destroy', $batch))->assertNotFound();
        $this->assertNotNull(Batch::withoutGlobalScopes()->find($batch->id));
    }

    public function test_fees_are_tenant_isolated(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Student', 'parent_mobile' => '9990003333', 'course_id' => $course->id]);
        $studentFee = $student->fee;
        $transaction = FeeTransaction::create([
            'tenant_id' => $admin->tenant_id, 'student_fee_id' => $studentFee->id,
            'amount' => 100, 'payment_mode' => 'cash', 'collected_by' => $admin->id,
        ]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.fees.show', $studentFee))->assertNotFound();
        $this->actingAs($adminB)->patch(route('institute.fees.discount.update', $studentFee), ['discount_amount' => 50])->assertNotFound();
        $this->actingAs($adminB)->get(route('institute.fees.transactions.receipt', $transaction))->assertNotFound();
        $this->assertNotNull(FeeTransaction::withoutGlobalScopes()->find($transaction->id));
    }

    public function test_attendance_is_tenant_isolated(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Student', 'parent_mobile' => '9990004444', 'course_id' => $course->id]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        // B's admin has full attendance permissions on their own tenant, but
        // A's student simply doesn't exist inside B's tenant-scoped lookup.
        $this->actingAs($adminB)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$student->id => 'present'],
        ])->assertNotFound();

        $this->assertDatabaseMissing('attendance', ['student_id' => $student->id]);
    }

    public function test_roles_are_tenant_isolated(): void
    {
        $admin = $this->tenantAdmin();
        $role = Role::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Custom Role', 'is_system' => false]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.roles.edit', $role))->assertNotFound();
        $this->actingAs($adminB)->put(route('institute.roles.update', $role), ['name' => 'Hijacked'])->assertNotFound();
        $this->actingAs($adminB)->delete(route('institute.roles.destroy', $role))->assertNotFound();
        $this->assertNotNull(Role::withoutGlobalScopes()->find($role->id));
    }

    public function test_staff_are_tenant_isolated(): void
    {
        $admin = $this->tenantAdmin();
        $role = Role::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Staff Role', 'is_system' => false]);
        $staff = User::create([
            'tenant_id' => $admin->tenant_id, 'name' => 'Institute A Staff', 'email' => 'staff-a@example.com',
            'password' => 'password', 'role' => \App\Enums\Role::TenantUser, 'role_id' => $role->id, 'is_active' => true,
        ]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.staff.edit', $staff))->assertNotFound();

        // Uses adminB's own (legitimate, same-tenant) role_id so the update
        // payload itself passes validation — isolating whether the
        // controller's own assertSameTenant() guard blocks it, rather than
        // the request incidentally failing validation for an unrelated
        // reason (submitting institute A's role_id would fail role_id
        // validation for institute B regardless of the staff record).
        $this->actingAs($adminB)->put(route('institute.staff.update', $staff), [
            'name' => 'Hijacked', 'email' => 'staff-a@example.com', 'role_id' => $adminB->role_id,
        ])->assertNotFound();

        $this->actingAs($adminB)->patch(route('institute.staff.toggle-status', $staff))->assertNotFound();
        $this->assertNotNull(User::find($staff->id));
    }

    /**
     * Not BelongsToTenant-scoped like the 8 above (see Sprint 9D's notes on
     * why - the column is institute_id, not tenant_id, and whatsapp_settings
     * additionally needs null-institute rows to stay visible everywhere for
     * the platform-default fallback) - isolation here is the WhatsApp
     * Settings page's own explicit institute_id filter, confirmed directly.
     */
    public function test_whatsapp_logs_are_tenant_isolated(): void
    {
        $admin = $this->tenantAdmin();
        $professional = Plan::where('name', 'Professional')->firstOrFail();
        $admin->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);
        WhatsAppLog::create([
            'institute_id' => $admin->tenant_id, 'template_name' => 'fee_reminder',
            'recipient_number' => '9990007777', 'status' => 'sent',
        ]);

        [$instituteB, $adminB] = $this->createSecondInstituteWithAdmin();
        $adminB->institute->subscription->update(['status' => 'active', 'plan_id' => $professional->id]);

        $this->actingAs($adminB)->get(route('institute.whatsapp.index'))
            ->assertOk()
            ->assertDontSee('9990007777');
    }

    /**
     * A different angle from the 8 checks above: rather than Institute B
     * reaching directly for one of Institute A's records, B tries to create
     * its OWN record that points at Institute A's course/batch via a
     * submitted foreign key. No single sprint's own tests would catch this
     * — Sprint 2's student tests predate course_id existing at all, and
     * Sprint 3's course tests have no reason to think about who's assigning
     * students to them.
     */
    public function test_cannot_assign_a_student_to_another_institutes_course_or_batch_by_id(): void
    {
        $admin = $this->tenantAdmin();
        $courseA = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Institute A Course', 'duration' => '1 month', 'fee' => 500]);
        $batchA = Batch::create(['tenant_id' => $admin->tenant_id, 'course_id' => $courseA->id, 'name' => 'Institute A Batch', 'time_slot' => '5 PM - 6 PM']);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $response = $this->actingAs($adminB)->post(route('institute.students.store'), [
            'name' => 'Cross Tenant Student',
            'parent_mobile' => '9990005555',
            'course_id' => $courseA->id,
        ]);

        $response->assertSessionHasErrors('course_id');
        $this->assertDatabaseMissing('students', ['name' => 'Cross Tenant Student']);

        $responseWithBatch = $this->actingAs($adminB)->post(route('institute.students.store'), [
            'name' => 'Cross Tenant Student 2',
            'parent_mobile' => '9990006666',
            'batch_id' => $batchA->id,
        ]);

        $responseWithBatch->assertSessionHasErrors('batch_id');
    }

    private function tenantAdmin(): User
    {
        return User::where('email', 'admin@brightcoaching.com')->firstOrFail();
    }
}
