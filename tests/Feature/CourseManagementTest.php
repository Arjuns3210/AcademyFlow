<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\Concerns\CreatesViewOnlyUser;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use CreatesSecondInstitute, CreatesViewOnlyUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_with_add_permission_can_create_course(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $response = $this->actingAs($instituteAdmin)->post(route('institute.courses.store'), [
            'name' => 'Spoken English',
            'duration_value' => 3,
            'duration_unit' => 'Months',
            'fee' => 4999,
        ]);

        $response->assertRedirect(route('institute.courses.index'));

        $course = Course::where('name', 'Spoken English')->firstOrFail();
        $this->assertSame($instituteAdmin->tenant_id, $course->tenant_id);
        $this->assertSame('3 Months', $course->duration);
    }

    public function test_user_without_delete_permission_gets_403_on_delete_route(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $course = Course::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Viewer Test Course',
            'duration' => '1 month',
            'fee' => 500,
        ]);

        $viewer = $this->createViewOnlyUser($instituteAdmin->tenant_id, 'courses');

        $this->actingAs($viewer)
            ->delete(route('institute.courses.destroy', $course))
            ->assertForbidden();

        $this->assertNotNull(Course::find($course->id));
    }

    public function test_staff_from_institute_a_cannot_view_edit_or_delete_institute_b_course(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $courseA = Course::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Institute A Course',
            'duration' => '2 months',
            'fee' => 1000,
        ]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.courses.edit', $courseA))->assertNotFound();
        $this->actingAs($adminB)->delete(route('institute.courses.destroy', $courseA))->assertNotFound();

        $this->actingAs($adminB)->get(route('institute.courses.index'))
            ->assertOk()
            ->assertDontSee('Institute A Course');

        $this->assertNotNull(Course::withoutGlobalScopes()->find($courseA->id));
    }

    public function test_cannot_delete_course_with_active_students_assigned(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $course = Course::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Course With Students',
            'duration' => '6 months',
            'fee' => 2000,
        ]);

        Student::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Enrolled Student',
            'parent_mobile' => '9876500010',
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($instituteAdmin)->delete(route('institute.courses.destroy', $course));

        $response->assertSessionHasErrors('course');
        $this->assertNull($course->fresh()->deleted_at);
    }

    public function test_soft_deleted_course_does_not_appear_in_batch_create_form(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $course = Course::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Soon Deleted Course',
            'duration' => '1 month',
            'fee' => 500,
        ]);
        $course->delete();

        $this->actingAs($instituteAdmin)->get(route('institute.batches.create'))
            ->assertOk()
            ->assertDontSee('Soon Deleted Course');
    }
}
