<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\Concerns\CreatesViewOnlyUser;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use CreatesSecondInstitute, CreatesViewOnlyUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_with_add_permission_can_create_teacher(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $response = $this->actingAs($instituteAdmin)->post(route('institute.teachers.store'), [
            'name' => 'Anita Rao',
            'mobile' => '9876543220',
        ]);

        $response->assertRedirect(route('institute.teachers.index'));

        $teacher = Teacher::where('name', 'Anita Rao')->firstOrFail();
        $this->assertSame($instituteAdmin->tenant_id, $teacher->tenant_id);
    }

    public function test_user_without_delete_permission_gets_403_on_delete_route(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $teacher = Teacher::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Viewer Test Teacher',
            'mobile' => '9876543221',
        ]);

        $viewer = $this->createViewOnlyUser($instituteAdmin->tenant_id, 'teachers');

        $this->actingAs($viewer)
            ->delete(route('institute.teachers.destroy', $teacher))
            ->assertForbidden();

        $this->assertNotNull(Teacher::find($teacher->id));
    }

    public function test_staff_from_institute_a_cannot_view_edit_or_delete_institute_b_teacher(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $teacherA = Teacher::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Institute A Teacher',
            'mobile' => '9876543222',
        ]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.teachers.edit', $teacherA))->assertNotFound();
        $this->actingAs($adminB)->delete(route('institute.teachers.destroy', $teacherA))->assertNotFound();

        $this->actingAs($adminB)->get(route('institute.teachers.index'))
            ->assertOk()
            ->assertDontSee('Institute A Teacher');

        $this->assertNotNull(Teacher::withoutGlobalScopes()->find($teacherA->id));
    }

    public function test_cannot_delete_teacher_with_batch_assigned(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $teacher = Teacher::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Busy Teacher',
            'mobile' => '9876543223',
        ]);

        $course = Course::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Course For Busy Teacher',
            'duration' => '3 months',
            'fee' => 1500,
        ]);

        Batch::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Batch A',
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'time_slot' => '5 PM - 6 PM',
        ]);

        $response = $this->actingAs($instituteAdmin)->delete(route('institute.teachers.destroy', $teacher));

        $response->assertSessionHasErrors('teacher');
        $this->assertNull($teacher->fresh()->deleted_at);
    }

    public function test_soft_deleted_teacher_does_not_appear_in_batch_create_form(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $teacher = Teacher::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Soon Deleted Teacher',
            'mobile' => '9876543224',
        ]);
        $teacher->delete();

        $this->actingAs($instituteAdmin)->get(route('institute.batches.create'))
            ->assertOk()
            ->assertDontSee('Soon Deleted Teacher');
    }
}
