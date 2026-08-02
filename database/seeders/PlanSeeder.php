<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter', 'price' => 499, 'billing_cycle' => 'monthly', 'staff_limit' => 3],
            ['name' => 'Professional', 'price' => 999, 'billing_cycle' => 'monthly', 'staff_limit' => 10],
            ['name' => 'Enterprise', 'price' => 2499, 'billing_cycle' => 'monthly', 'staff_limit' => null],
            ['name' => 'Starter Yearly', 'price' => 5389.20, 'billing_cycle' => 'yearly', 'staff_limit' => 3],
            ['name' => 'Professional Yearly', 'price' => 10789.20, 'billing_cycle' => 'yearly', 'staff_limit' => 10],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
