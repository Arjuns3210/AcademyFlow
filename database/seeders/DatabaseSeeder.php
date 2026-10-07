<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            InstituteTypeSeeder::class,
            PlanSeeder::class,
            PlanModuleSeeder::class,
            SuperAdminSeeder::class,
            DemoInstituteSeeder::class,
            CoachingDemoDataSeeder::class,
        ]);
    }
}
