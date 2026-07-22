<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Closure;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesUserWithActions;
use Tests\TestCase;

/**
 * Confirms each of view/add/edit/delete/status is checked independently —
 * a role granted every OTHER action on a module still 403s on the one
 * action it's missing, proving the gate checks the specific action rather
 * than "has some permission on this module". Individual sprints only ever
 * tested a couple of these combinations per module (usually delete, plus
 * whatever that module's own step needed); this sweep is the first place
 * all five actions get checked for every standard CRUD+status module in
 * one pass.
 */
class PermissionSweepTest extends TestCase
{
    use CreatesUserWithActions, RefreshDatabase;

    private const ALL_ACTIONS = ['view', 'add', 'edit', 'delete', 'status'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_every_students_action_requires_its_own_specific_permission(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Sweep Course', 'duration' => '1 month', 'fee' => 500]);
        $student = Student::create(['tenant_id' => $admin->tenant_id, 'name' => 'Sweep Student', 'parent_mobile' => '9990001111', 'course_id' => $course->id]);

        $this->assertActionGated('students', 'view', fn (User $u) => $this->actingAs($u)->get(route('institute.students.index')));
        $this->assertActionGated('students', 'add', fn (User $u) => $this->actingAs($u)->post(route('institute.students.store'), ['name' => 'X', 'parent_mobile' => '9990002222']));
        $this->assertActionGated('students', 'edit', fn (User $u) => $this->actingAs($u)->put(route('institute.students.update', $student), ['name' => 'Renamed', 'parent_mobile' => '9990001111']));
        $this->assertActionGated('students', 'delete', fn (User $u) => $this->actingAs($u)->delete(route('institute.students.destroy', $student)));
        $this->assertActionGated('students', 'status', fn (User $u) => $this->actingAs($u)->patch(route('institute.students.update-status', $student), ['status' => 'inactive']));
    }

    public function test_every_courses_action_requires_its_own_specific_permission(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Sweep Course', 'duration' => '1 month', 'fee' => 500]);

        $this->assertActionGated('courses', 'view', fn (User $u) => $this->actingAs($u)->get(route('institute.courses.index')));
        $this->assertActionGated('courses', 'add', fn (User $u) => $this->actingAs($u)->post(route('institute.courses.store'), ['name' => 'New Course', 'duration' => '1 month', 'fee' => 500]));
        $this->assertActionGated('courses', 'edit', fn (User $u) => $this->actingAs($u)->put(route('institute.courses.update', $course), ['name' => 'Renamed', 'duration' => '1 month', 'fee' => 600]));
        $this->assertActionGated('courses', 'delete', fn (User $u) => $this->actingAs($u)->delete(route('institute.courses.destroy', $course)));
        $this->assertActionGated('courses', 'status', fn (User $u) => $this->actingAs($u)->patch(route('institute.courses.update-status', $course)));
    }

    public function test_every_teachers_action_requires_its_own_specific_permission(): void
    {
        $admin = $this->tenantAdmin();
        $teacher = Teacher::create(['tenant_id' => $admin->tenant_id, 'name' => 'Sweep Teacher', 'mobile' => '9990003333']);

        $this->assertActionGated('teachers', 'view', fn (User $u) => $this->actingAs($u)->get(route('institute.teachers.index')));
        $this->assertActionGated('teachers', 'add', fn (User $u) => $this->actingAs($u)->post(route('institute.teachers.store'), ['name' => 'New Teacher', 'mobile' => '9990004444']));
        $this->assertActionGated('teachers', 'edit', fn (User $u) => $this->actingAs($u)->put(route('institute.teachers.update', $teacher), ['name' => 'Renamed', 'mobile' => '9990003333']));
        $this->assertActionGated('teachers', 'delete', fn (User $u) => $this->actingAs($u)->delete(route('institute.teachers.destroy', $teacher)));
        $this->assertActionGated('teachers', 'status', fn (User $u) => $this->actingAs($u)->patch(route('institute.teachers.update-status', $teacher)));
    }

    public function test_every_batches_action_requires_its_own_specific_permission(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'Sweep Course', 'duration' => '1 month', 'fee' => 500]);
        $batch = Batch::create(['tenant_id' => $admin->tenant_id, 'course_id' => $course->id, 'name' => 'Sweep Batch', 'time_slot' => '5 PM - 6 PM']);

        $this->assertActionGated('batches', 'view', fn (User $u) => $this->actingAs($u)->get(route('institute.batches.index')));
        $this->assertActionGated('batches', 'add', fn (User $u) => $this->actingAs($u)->post(route('institute.batches.store'), ['course_id' => $course->id, 'name' => 'New Batch', 'time_slot' => '6 PM - 7 PM']));
        $this->assertActionGated('batches', 'edit', fn (User $u) => $this->actingAs($u)->put(route('institute.batches.update', $batch), ['course_id' => $course->id, 'name' => 'Renamed', 'time_slot' => '5 PM - 6 PM']));
        $this->assertActionGated('batches', 'delete', fn (User $u) => $this->actingAs($u)->delete(route('institute.batches.destroy', $batch)));
        $this->assertActionGated('batches', 'status', fn (User $u) => $this->actingAs($u)->patch(route('institute.batches.update-status', $batch)));
    }

    /**
     * Grants every action on $moduleSlug except $withheldAction, then
     * confirms $makeRequest — expected to need exactly that action — 403s.
     */
    private function assertActionGated(string $moduleSlug, string $withheldAction, Closure $makeRequest): void
    {
        $admin = $this->tenantAdmin();
        $grantedActions = array_values(array_diff(self::ALL_ACTIONS, [$withheldAction]));
        $user = $this->createUserWithActions($admin->tenant_id, $moduleSlug, $grantedActions);

        /** @var TestResponse $response */
        $response = $makeRequest($user);

        $response->assertForbidden();
    }

    private function tenantAdmin(): User
    {
        return User::where('email', 'admin@brightcoaching.com')->firstOrFail();
    }
}
