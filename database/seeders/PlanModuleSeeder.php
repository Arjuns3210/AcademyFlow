<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanModule;
use Illuminate\Database\Seeder;

class PlanModuleSeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            'Starter' => [],
            'Professional' => ['whatsapp_reminder', 'dashboard_charts'],
            'Enterprise' => ['whatsapp_reminder', 'qr_attendance', 'student_login', 'parent_login', 'dashboard_charts'],
            'Starter Yearly' => [],
            'Professional Yearly' => ['whatsapp_reminder', 'dashboard_charts'],
        ];

        foreach ($mapping as $planName => $moduleKeys) {
            $plan = Plan::where('name', $planName)->first();

            if (! $plan) {
                continue;
            }

            foreach ($moduleKeys as $moduleKey) {
                PlanModule::updateOrCreate(['plan_id' => $plan->id, 'module_key' => $moduleKey]);
            }
        }
    }
}
