<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter', 'price' => 499, 'billing_cycle' => 'monthly'],
            ['name' => 'Professional', 'price' => 999, 'billing_cycle' => 'monthly'],
            ['name' => 'Enterprise', 'price' => 2499, 'billing_cycle' => 'monthly'],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
