<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'max_users' => 10,
                'max_branches' => 5,
                'price' => 10000.00,
                'duration_days' => 365
            ],
            [
                'name' => 'Professional',
                'max_users' => 50,
                'max_branches' => 10,
                'price' => 50000.00,
                'duration_days' => 365
            ],
            [
                'name' => 'Enterprise',
                'max_users' => 250,
                'max_branches' => 50,
                'price' => 250000.00,
                'duration_days' => 365
            ]
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
