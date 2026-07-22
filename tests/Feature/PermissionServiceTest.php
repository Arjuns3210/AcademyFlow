<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\InstituteModule;
use App\Models\Module;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_super_admin_passes_any_permission_check(): void
    {
        $superAdmin = User::where('role', RoleEnum::SuperAdmin)->firstOrFail();
        $service = app(PermissionService::class);

        foreach (['view', 'add', 'edit', 'delete', 'status'] as $action) {
            $this->assertTrue($service->can($superAdmin, 'students', $action));
        }

        // Bypass is unconditional — doesn't matter that this module/institute
        // combination doesn't even exist.
        $this->assertTrue($service->can($superAdmin, 'no-such-module', 'view'));
    }

    public function test_demo_institute_admin_passes_all_actions_on_enabled_modules(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $service = app(PermissionService::class);

        foreach (Module::pluck('slug') as $slug) {
            foreach (['view', 'add', 'edit', 'delete', 'status'] as $action) {
                $this->assertTrue(
                    $service->can($admin, $slug, $action),
                    "Expected Institute Admin to have {$action} on {$slug}"
                );
            }
        }
    }

    public function test_user_with_no_role_id_fails_every_check(): void
    {
        $institute = User::where('email', 'admin@brightcoaching.com')->firstOrFail()->tenant_id;

        $user = User::factory()->create([
            'tenant_id' => $institute,
            'role' => RoleEnum::TenantUser,
            'role_id' => null,
        ]);

        $service = app(PermissionService::class);

        foreach (['view', 'add', 'edit', 'delete', 'status'] as $action) {
            $this->assertFalse($service->can($user, 'students', $action));
        }
    }

    public function test_disabled_module_short_circuits_before_role_permission_check(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $studentsModule = Module::where('slug', 'students')->firstOrFail();

        // Confirm the role really does have can_view = true on this module —
        // checked directly against role_permissions, not via PermissionService,
        // since that service is a singleton and calling it here would cache
        // the (still-enabled) module state before we disable it below.
        $rolePermission = $admin->roleRecord->permissions()
            ->where('module_id', $studentsModule->id)
            ->firstOrFail();
        $this->assertTrue($rolePermission->can_view);

        InstituteModule::where('institute_id', $admin->tenant_id)
            ->where('module_id', $studentsModule->id)
            ->update(['is_enabled' => false]);

        // First (and only) PermissionService call in this test, so its
        // per-tenant cache is populated fresh, after the toggle above.
        $this->assertFalse(
            app(PermissionService::class)->can($admin, 'students', 'view')
        );
    }

    public function test_invalid_action_throws_invalid_argument_exception(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        $this->expectException(InvalidArgumentException::class);

        app(PermissionService::class)->can($admin, 'students', 'export');
    }
}
