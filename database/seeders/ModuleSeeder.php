<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * The platform-wide module catalog. Super Admin can add more later via
     * the Modules screen — this is just the Sprint 1 starting set.
     */
    public function run(): void
    {
        $modules = [
            ['name' => 'Students', 'slug' => 'students', 'icon' => 'bi-people'],
            ['name' => 'Batches', 'slug' => 'batches', 'icon' => 'bi-grid-3x3-gap'],
            ['name' => 'Teachers', 'slug' => 'teachers', 'icon' => 'bi-person-workspace'],
            ['name' => 'Courses', 'slug' => 'courses', 'icon' => 'bi-journal-bookmark'],
            ['name' => 'Fees', 'slug' => 'fees', 'icon' => 'bi-cash-coin'],
            ['name' => 'Attendance', 'slug' => 'attendance', 'icon' => 'bi-calendar-check'],
            ['name' => 'Reports', 'slug' => 'reports', 'icon' => 'bi-bar-chart'],
            ['name' => 'Settings', 'slug' => 'settings', 'icon' => 'bi-gear'],
        ];

        foreach ($modules as $index => $module) {
            Module::updateOrCreate(
                ['slug' => $module['slug']],
                [...$module, 'sort_order' => $index + 1, 'is_active' => true]
            );
        }
    }
}
