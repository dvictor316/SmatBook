<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            // Monthly Plans
            ['name' => 'Basic Monthly', 'price' => 6000, 'billing_cycle' => 'monthly', 'recommended' => 0],
            ['name' => 'Pro Monthly', 'price' => 15000, 'billing_cycle' => 'monthly', 'recommended' => 1],
            ['name' => 'Enterprise Monthly', 'price' => 35000, 'billing_cycle' => 'monthly', 'recommended' => 0],
            
            // Yearly Plans
            ['name' => 'Basic Yearly', 'price' => 60000, 'billing_cycle' => 'yearly', 'recommended' => 0],
            ['name' => 'Pro Yearly', 'price' => 150000, 'billing_cycle' => 'yearly', 'recommended' => 0],
            ['name' => 'Enterprise Yearly', 'price' => 350000, 'billing_cycle' => 'yearly', 'recommended' => 0],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name'], 'billing_cycle' => $plan['billing_cycle']],
                [
                    'price' => $plan['price'],
                    'recommended' => $plan['recommended'],
                    'is_active' => 1,
                    'status' => 'active',
                ]
            );
        }
    }
}