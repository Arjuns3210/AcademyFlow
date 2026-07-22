<?php

namespace Tests\Concerns;

use App\Enums\Role as RoleEnum;
use App\Models\Institute;
use App\Models\InstituteType;
use App\Models\User;
use App\Services\InstituteProvisioningService;

/**
 * Spins up a second, fully-provisioned institute + admin for tenant-isolation
 * tests, via the real InstituteProvisioningService rather than a hand-rolled
 * shortcut, so isolation tests exercise the same path production traffic does.
 */
trait CreatesSecondInstitute
{
    /**
     * @return array{0: Institute, 1: User}
     */
    private function createSecondInstituteWithAdmin(): array
    {
        $type = InstituteType::firstOrFail();

        $institute = Institute::create([
            'institute_type_id' => $type->id,
            'name' => 'Institute B',
            'status' => 'active',
        ]);

        $service = app(InstituteProvisioningService::class);
        $service->enableModulesFromType($institute);
        $role = $service->createDefaultAdminRole($institute);
        $service->startTrialSubscription($institute);

        $admin = User::create([
            'tenant_id' => $institute->id,
            'name' => 'Institute B Admin',
            'email' => 'admin@institute-b.example',
            'password' => 'password',
            'role' => RoleEnum::TenantUser,
            'role_id' => $role->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return [$institute, $admin];
    }
}
