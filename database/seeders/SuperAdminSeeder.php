<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@academyflow.in'],
            [
                'name' => 'Arjun Singh',
                'password' => 'password',
                'role' => Role::SuperAdmin,
                'tenant_id' => null,
                'role_id' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}
