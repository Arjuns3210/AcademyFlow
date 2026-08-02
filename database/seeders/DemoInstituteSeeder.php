<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Institute;
use App\Models\InstituteType;
use App\Models\User;
use App\Services\InstituteProvisioningService;
use Illuminate\Database\Seeder;

class DemoInstituteSeeder extends Seeder
{
    public function run(): void
    {
        $service = new InstituteProvisioningService;

        $type = InstituteType::where('name', 'Coaching Institute')->firstOrFail();

        $institute = Institute::updateOrCreate(
            ['name' => 'Bright Coaching Classes'],
            [
                'institute_type_id' => $type->id,
                'address' => '221B, MG Road, Bengaluru, Karnataka',
                'contact' => '+91-9876543210',
                'email' => 'contact@brightcoaching.com',
                'status' => 'active',
            ]
        );

        $service->enableModulesFromType($institute);

        $adminRole = $service->createDefaultAdminRole($institute);
        $service->startTrialSubscription($institute);

        User::updateOrCreate(
            ['email' => 'admin@brightcoaching.com'],
            [
                'name' => 'Priya Sharma',
                'password' => 'password',
                'role' => Role::TenantUser,
                'tenant_id' => $institute->id,
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
                'is_demo_account' => true,
            ]
        );
    }
}
