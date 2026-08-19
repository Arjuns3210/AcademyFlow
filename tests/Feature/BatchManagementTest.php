<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\Concerns\CreatesViewOnlyUser;
use Tests\TestCase;

class BatchManagementTest extends TestCase
{
    use CreatesSecondInstitute, CreatesViewOnlyUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_with_add_permission_can_create_batch(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = $this->createCourse($instituteAdmin->tenant_id);

        $response = $this->actingAs($instituteAdmin)->post(route('institute.batches.store'), [
            'name' => 'Morning Batch',
            'course_id' => $course->id,
            'time_slot_start' => '07:00',
            'time_slot_end' => '08:00',
        ]);

        $response->assertRedirect(route('institute.batches.index'));

        $batch = Batch::where('name', 'Morning Batch')->firstOrFail();
        $this->assertSame($instituteAdmin->tenant_id, $batch->tenant_id);
        $this->assertSame('7:00 AM - 8:00 AM', $batch->time_slot);
    }

    public function test_user_without_delete_permission_gets_403_on_delete_route(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = $this->createCourse($instituteAdmin->tenant_id);

        $batch = Batch::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Viewer Test Batch',
            'course_id' => $course->id,
            'time_slot' => '5 PM - 6 PM',
        ]);

        $viewer = $this->createViewOnlyUser($instituteAdmin->tenant_id, 'batches');

        $this->actingAs($viewer)
            ->delete(route('institute.batches.destroy', $batch))
            ->assertForbidden();

        $this->assertNotNull(Batch::find($batch->id));
    }

    public function test_staff_from_institute_a_cannot_view_edit_or_delete_institute_b_batch(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = $this->createCourse($instituteAdmin->tenant_id);

        $batchA = Batch::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Institute A Batch',
            'course_id' => $course->id,
            'time_slot' => '5 PM - 6 PM',
        ]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.batches.edit', $batchA))->assertNotFound();
        $this->actingAs($adminB)->delete(route('institute.batches.destroy', $batchA))->assertNotFound();

        $this->actingAs($adminB)->get(route('institute.batches.index'))
            ->assertOk()
            ->assertDontSee('Institute A Batch');

        $this->assertNotNull(Batch::withoutGlobalScopes()->find($batchA->id));
    }

    public function test_cannot_delete_batch_with_students_assigned(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = $this->createCourse($instituteAdmin->tenant_id);

        $batch = Batch::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Batch With Students',
            'course_id' => $course->id,
            'time_slot' => '5 PM - 6 PM',
        ]);

        Student::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Assigned Student',
            'parent_mobile' => '9876500020',
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            // Even an inactive/dropped student should still block deletion —
            // Batch's own spec wording never qualified this to "active" only,
            // unlike Course's guard.
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($instituteAdmin)->delete(route('institute.batches.destroy', $batch));

        $response->assertSessionHasErrors('batch');
        $this->assertNull($batch->fresh()->deleted_at);
    }

    public function test_full_batch_rejects_new_student_assignment_with_clear_error(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = $this->createCourse($instituteAdmin->tenant_id);

        $batch = Batch::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Full Batch',
            'course_id' => $course->id,
            'time_slot' => '5 PM - 6 PM',
            'capacity' => 1,
        ]);

        Student::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Existing Student',
            'parent_mobile' => '9876500030',
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($instituteAdmin)->post(route('institute.students.store'), [
            'name' => 'Overflow Student',
            'parent_mobile' => '9876500031',
            'course_id' => $course->id,
            'batch_id' => $batch->id,
        ]);

        $response->assertSessionHasErrors('batch_id');
        $this->assertDatabaseMissing('students', ['name' => 'Overflow Student']);
        $this->assertSame(1, $batch->fresh()->enrolledCount());
    }

    public function test_editing_student_already_in_full_batch_does_not_reject_their_own_slot(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = $this->createCourse($instituteAdmin->tenant_id);

        $batch = Batch::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Full Batch',
            'course_id' => $course->id,
            'time_slot' => '5 PM - 6 PM',
            'capacity' => 1,
        ]);

        $student = Student::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Existing Student',
            'parent_mobile' => '9876500032',
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'status' => 'active',
        ]);

        // Re-saving the student's own unchanged batch must not count their
        // own slot against themselves, even though the batch is at capacity.
        $response = $this->actingAs($instituteAdmin)->put(route('institute.students.update', $student), [
            'name' => 'Existing Student',
            'parent_mobile' => '9876500032',
            'course_id' => $course->id,
            'batch_id' => $batch->id,
        ]);

        $response->assertSessionDoesntHaveErrors('batch_id');
        $response->assertRedirect(route('institute.students.index'));
    }

    public function test_soft_deleted_batch_does_not_appear_in_course_batches_json(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $course = $this->createCourse($instituteAdmin->tenant_id);

        $batch = Batch::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Soon Deleted Batch',
            'course_id' => $course->id,
            'time_slot' => '5 PM - 6 PM',
        ]);
        $batch->delete();

        $this->actingAs($instituteAdmin)
            ->getJson(route('institute.students.course-batches', $course))
            ->assertOk()
            ->assertJsonCount(0);
    }

    private function createCourse(int $tenantId): Course
    {
        return Course::create([
            'tenant_id' => $tenantId,
            'name' => 'Course For Batches',
            'duration' => '3 months',
            'fee' => 1000,
        ]);
    }
}
