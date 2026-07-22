<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\Concerns\CreatesViewOnlyUser;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use CreatesSecondInstitute, CreatesViewOnlyUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_with_add_permission_can_create_student_with_valid_auto_generated_code(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $response = $this->actingAs($instituteAdmin)->post(route('institute.students.store'), [
            'name' => 'Aarav Sharma',
            'parent_mobile' => '9876543210',
            'admission_date' => '2026-07-01',
        ]);

        $response->assertRedirect(route('institute.students.index'));

        $student = Student::where('name', 'Aarav Sharma')->firstOrFail();
        $this->assertSame($instituteAdmin->tenant_id, $student->tenant_id);
        $this->assertMatchesRegularExpression('/^[A-Z]+-\d{4}-\d{4}$/', $student->student_code);
    }

    public function test_user_without_delete_permission_gets_403_on_delete_route(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $student = Student::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Priya Verma',
            'parent_mobile' => '9876543211',
        ]);

        $viewer = $this->createViewOnlyUser($instituteAdmin->tenant_id, 'students');

        $this->actingAs($viewer)
            ->delete(route('institute.students.destroy', $student))
            ->assertForbidden();

        $this->assertNotNull(Student::find($student->id));
    }

    public function test_staff_from_institute_a_cannot_view_edit_or_delete_institute_b_student(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $studentA = Student::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Institute A Student',
            'parent_mobile' => '9876543212',
        ]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.students.show', $studentA))->assertNotFound();
        $this->actingAs($adminB)->get(route('institute.students.edit', $studentA))->assertNotFound();
        $this->actingAs($adminB)->delete(route('institute.students.destroy', $studentA))->assertNotFound();

        $this->actingAs($adminB)->get(route('institute.students.index'))
            ->assertOk()
            ->assertDontSee('Institute A Student');

        // The record itself must survive Institute B's failed delete attempt.
        // withoutGlobalScopes() because we're still "logged in" as Institute
        // B above — the tenant scope alone would hide Institute A's row here
        // regardless of whether the delete actually went through.
        $this->assertNotNull(Student::withoutGlobalScopes()->find($studentA->id));
    }

    public function test_soft_deleted_student_hidden_from_default_list_but_visible_in_trash(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $student = Student::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Trashed Student',
            'parent_mobile' => '9876543213',
        ]);

        $this->actingAs($instituteAdmin)
            ->delete(route('institute.students.destroy', $student))
            ->assertRedirect(route('institute.students.index'));

        // The redirect target's first render legitimately contains a
        // one-time flash banner naming the student ("... moved to trash.") —
        // fetch it again to check the steady-state list, past that flash.
        $this->actingAs($instituteAdmin)->get(route('institute.students.index'));

        $this->actingAs($instituteAdmin)->get(route('institute.students.index'))
            ->assertOk()
            ->assertDontSee('Trashed Student');

        $this->actingAs($instituteAdmin)->get(route('institute.students.index', ['trashed' => 1]))
            ->assertOk()
            ->assertSee('Trashed Student');
    }
}
