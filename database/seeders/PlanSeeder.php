<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            // Annual plans. Legacy monthly records remain for subscription history but are inactive.
            ['name' => 'Starter Yearly', 'price' => 20000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 1],
            ['name' => 'Basic Yearly', 'price' => 80000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 2],
            ['name' => 'Pro Solo Yearly', 'price' => 100000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 3],
            ['name' => 'Pro Yearly', 'price' => 150000, 'billing_cycle' => 'yearly', 'recommended' => 1, 'user_limit' => 5],
            ['name' => 'Enterprise Solo Yearly', 'price' => 200000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 4],
            ['name' => 'Enterprise Yearly', 'price' => 300000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 10],
            ['name' => 'Hotel Yearly', 'price' => 200000, 'billing_cycle' => 'yearly', 'recommended' => 0, 'user_limit' => 8],
        ];

        Plan::query()->whereRaw('LOWER(billing_cycle) = ?', ['monthly'])->update([
            'is_active' => 0,
            'status' => 'inactive',
        ]);

        Plan::query()->whereIn('name', ['Starter Solo Yearly', 'Basic Solo Yearly'])->update([
            'is_active' => 0,
            'status' => 'inactive',
        ]);

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
