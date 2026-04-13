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
            ['name' => 'Basic Solo Monthly', 'price' => 3000, 'billing_cycle' => 'monthly', 'recommended' => 0, 'user_limit' => 1],
            ['name' => 'Basic Monthly', 'price' => 5500, 'billing_cycle' => 'monthly', 'recommended' => 0, 'user_limit' => 3],
            ['name' => 'Pro Solo Monthly', 'price' => 7000, 'billing_cycle' => 'monthly', 'recommended' => 0, 'user_limit' => 2],
            ['name' => 'Pro Monthly', 'price' => 19500, 'billing_cycle' => 'monthly', 'recommended' => 1, 'user_limit' => 5],
            ['name' => 'Enterprise Solo Monthly', 'price' => 15000, 'billing_cycle' => 'monthly', 'recommended' => 0, 'user_limit' => 3],
            ['name' => 'Enterprise Monthly', 'price' => 28500, 'billing_cycle' => 'monthly', 'recommended' => 0, 'user_limit' => 8],
            
            // Yearly Plans
            ['name' => 'Basic Solo Yearly', 'price' => 30000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 1],
            ['name' => 'Basic Yearly', 'price' => 55000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 3],
            ['name' => 'Pro Solo Yearly', 'price' => 70000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 2],
            ['name' => 'Pro Yearly', 'price' => 195000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 5],
            ['name' => 'Enterprise Solo Yearly', 'price' => 150000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 3],
            ['name' => 'Enterprise Yearly', 'price' => 285000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 8],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name'], 'billing_cycle' => $plan['billing_cycle']],
                [
                    'price' => $plan['price'],
                    'recommended' => $plan['recommended'],
                    'is_active' => 1,
                    'status' => 'active',
                    'user_limit' => $plan['user_limit'],
                ]
            );
        }
    }
}
