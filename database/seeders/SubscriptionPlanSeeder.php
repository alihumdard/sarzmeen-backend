<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Perfect for individual property owners with a few listings.',
                'price' => 1999.00,
                'duration_days' => 30,
                'listing_limit' => 5,
                'featured_limit' => 0,
                'image_limit' => 10,
                'priority_support' => false,
                'analytics_access' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Ideal for agents and small agencies with moderate listings.',
                'price' => 4999.00,
                'duration_days' => 30,
                'listing_limit' => 25,
                'featured_limit' => 3,
                'image_limit' => 20,
                'priority_support' => false,
                'analytics_access' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Best for established agencies with high volume listings.',
                'price' => 9999.00,
                'duration_days' => 30,
                'listing_limit' => 100,
                'featured_limit' => 10,
                'image_limit' => 30,
                'priority_support' => true,
                'analytics_access' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited access for large agencies and developers.',
                'price' => 24999.00,
                'duration_days' => 30,
                'listing_limit' => 500,
                'featured_limit' => 50,
                'image_limit' => 50,
                'priority_support' => true,
                'analytics_access' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
