<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\Institute;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\TestCase;

class InstituteRoleStaffManagementTest extends TestCase
{
    use CreatesSecondInstitute, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_institute_admin_can_create_role_and_assigned_staff_permissions_match_exactly(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $students = \App\Models\Module::where('slug', 'students')->firstOrFail();
        $attendance = \App\Models\Module::where('slug', 'attendance')->firstOrFail();
        $fees = \App\Models\Module::where('slug', 'fees')->firstOrFail();

        $roleResponse = $this->actingAs($instituteAdmin)->post(route('institute.roles.store'), [
            'name' => 'Teacher',
            'permissions' => [
                $students->id => ['view' => '1', 'edit' => '1'],
                $attendance->id => ['view' => '1', 'status' => '1'],
            ],
        ]);

        $roleResponse->assertRedirect(route('institute.roles.index'));

        $teacherRole = Role::where('tenant_id', $instituteAdmin->tenant_id)->where('name', 'Teacher')->firstOrFail();

        $staffResponse = $this->actingAs($instituteAdmin)->post(route('institute.staff.store'), [
            'name' => 'Ravi Kumar',
            'email' => 'ravi@brightcoaching.com',
            'role_id' => $teacherRole->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $staffResponse->assertRedirect(route('institute.staff.index'));

        $staff = User::where('email', 'ravi@brightcoaching.com')->firstOrFail();
        $this->assertSame($teacherRole->id, $staff->role_id);

        $permissions = app(PermissionService::class);

        // Exactly what was saved for Students: view + edit, nothing else.
        $this->assertTrue($permissions->can($staff, 'students', 'view'));
        $this->assertTrue($permissions->can($staff, 'students', 'edit'));
        $this->assertFalse($permissions->can($staff, 'students', 'add'));
        $this->assertFalse($permissions->can($staff, 'students', 'delete'));
        $this->assertFalse($permissions->can($staff, 'students', 'status'));

        // Exactly what was saved for Attendance: view + status, nothing else.
        $this->assertTrue($permissions->can($staff, 'attendance', 'view'));
        $this->assertTrue($permissions->can($staff, 'attendance', 'status'));
        $this->assertFalse($permissions->can($staff, 'attendance', 'edit'));

        // Fees was never checked for this role, even though it's an enabled
        // module for the institute — should be false across the board.
        $this->assertFalse($permissions->can($staff, 'fees', 'view'));
        $this->assertFalse($permissions->can($staff, 'fees', 'edit'));
    }

    public function test_staff_from_institute_a_cannot_view_or_edit_institute_b_roles_and_staff(): void
    {
        $instituteA = Institute::where('name', 'Bright Coaching Classes')->firstOrFail();
        $adminA = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        [$instituteB, $adminB] = $this->createSecondInstituteWithAdmin();

        // Give Institute A's admin a custom role + a staff member to isolate against.
        $roleA = Role::create(['tenant_id' => $instituteA->id, 'name' => 'Front Desk A', 'is_system' => false]);
        $staffA = User::create([
            'tenant_id' => $instituteA->id,
            'name' => 'Staff A',
            'email' => 'staffa@brightcoaching.com',
            'password' => 'password',
            'role' => RoleEnum::TenantUser,
            'role_id' => $roleA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Institute B's admin must not be able to reach Institute A's role/staff by ID.
        $this->actingAs($adminB)->get(route('institute.roles.edit', $roleA))->assertNotFound();
        $this->actingAs($adminB)->get(route('institute.staff.edit', $staffA))->assertNotFound();

        // And Institute B's own listing pages must not include Institute A's data.
        $this->actingAs($adminB)->get(route('institute.roles.index'))
            ->assertOk()
            ->assertDontSee('Front Desk A');

        $this->actingAs($adminB)->get(route('institute.staff.index'))
            ->assertOk()
            ->assertDontSee('Staff A');
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $staff = User::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'name' => 'Deactivated Staff',
            'email' => 'deactivated@brightcoaching.com',
            'password' => 'password123',
            'role' => RoleEnum::TenantUser,
            'role_id' => $instituteAdmin->role_id,
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'deactivated',
            session('errors')->get('email')[0]
        );
    }
}
