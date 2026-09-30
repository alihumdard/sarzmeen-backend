<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ProjectCategory::all()->keyBy('slug');
        $locations = Location::all()->keyBy('slug');

        $projects = [
            [
                'name' => 'Bahria Orchard Lahore',
                'slug' => 'bahria-orchard-lahore',
                'category' => 'residential-project',
                'developer' => 'Bahria Town Pvt Ltd',
                'location' => 'bahria-town-lahore',
                'full_location' => 'Raiwind Road, Lahore, Punjab, Pakistan',
                'status' => 'Ready to Move',
                'price_from' => 'Starting from PKR 45 Lac',
                'total_area' => '2000 Kanal',
                'total_units' => 5000,
                'description' => "Bahria Orchard is a premium residential community by Bahria Town.\nSpread over 2000 Kanal with world-class amenities.\nIdeal for families looking for a secure gated community.",
                'verified' => true,
                'featured' => true,
                'amenities' => ['Parks', 'Mosque', 'Community Center', 'Shopping Area', 'Sports Complex', 'Schools', '24/7 Security'],
                'highlights' => ['Gated community with 24/7 CCTV surveillance', 'Underground electricity and gas', 'Wide carpeted roads', 'Proximity to Lahore Ring Road'],
                'plot_sizes' => ['5 Marla', '8 Marla', '10 Marla', '1 Kanal'],
                'payment_plan' => [
                    ['label' => 'Down Payment', 'value' => '25%'],
                    ['label' => 'Monthly Installments', 'value' => '36 months'],
                    ['label' => 'On Possession', 'value' => '15%'],
                    ['label' => 'Balloting', 'value' => 'Completed'],
                ],
            ],
            [
                'name' => 'Capital Smart City',
                'slug' => 'capital-smart-city',
                'category' => 'residential-project',
                'developer' => 'Habib Rafiq Pvt Ltd',
                'location' => 'rawalpindi',
                'full_location' => 'M-2 Motorway Interchange, Rawalpindi, Punjab, Pakistan',
                'status' => 'Under Construction',
                'price_from' => 'Starting from PKR 22 Lac',
                'total_area' => '55000 Kanal',
                'total_units' => 40000,
                'description' => "Pakistan\'s first smart city featuring IoT-based infrastructure.\nStrategically located near the new Islamabad International Airport.\nA joint venture between FDHL and Habib Rafiq.",
                'verified' => true,
                'featured' => true,
                'amenities' => ['Smart Traffic System', 'Solar Power Grid', 'Fibre Internet', 'Helipad', 'Golf Course', 'Hospital', 'Theme Park'],
                'highlights' => ['NOC approved by RDA', 'Smart city features with IoT sensors', 'Directly accessible from M-2 Motorway', 'Overseas block with dedicated services'],
                'plot_sizes' => ['5 Marla', '7 Marla', '10 Marla', '1 Kanal', '2 Kanal'],
                'payment_plan' => [
                    ['label' => 'Down Payment', 'value' => '10%'],
                    ['label' => 'Confirmation', 'value' => '10%'],
                    ['label' => 'Monthly Installments', 'value' => '48 months'],
                    ['label' => 'On Possession', 'value' => '20%'],
                ],
            ],
            [
                'name' => 'Goldcrest Views DHA',
                'slug' => 'goldcrest-views-dha',
                'category' => 'high-raise-project',
                'developer' => 'Goldcrest Group',
                'location' => 'dha-defence-lahore',
                'full_location' => 'DHA Phase 4, Main Boulevard, Lahore',
                'status' => 'Under Construction',
                'price_from' => 'Starting from PKR 95 Lac',
                'total_area' => '4 Kanal',
                'total_units' => 320,
                'description' => "A 40-storey luxury high-rise in the heart of DHA Lahore.\nPremium apartments with panoramic city views.\nFood courts, retail and offices on lower floors.",
                'verified' => true,
                'featured' => true,
                'amenities' => ['Rooftop Pool', 'Gym', 'Spa', 'Food Court', 'Business Center', 'Underground Parking', 'Concierge'],
                'highlights' => ['DHA approved NOC', 'International standard construction', '40 floors with high-speed lifts', 'Earthquake-resistant structure'],
                'plot_sizes' => [],
                'payment_plan' => [
                    ['label' => 'Booking', 'value' => '20%'],
                    ['label' => 'Quarterly Installments', 'value' => '3 years'],
                    ['label' => 'On Possession', 'value' => '30%'],
                ],
            ],
            [
                'name' => 'Bahria Town Karachi',
                'slug' => 'bahria-town-karachi',
                'category' => 'residential-project',
                'developer' => 'Bahria Town Pvt Ltd',
                'location' => 'karachi',
                'full_location' => 'Super Highway, Karachi, Sindh, Pakistan',
                'status' => 'Ready to Move',
                'price_from' => 'Starting from PKR 35 Lac',
                'total_area' => '44000 Acres',
                'total_units' => 100000,
                'description' => "The largest private housing project in Asia.\nComplete township with every amenity imaginable.\nMultiple precincts catering to different budgets.",
                'verified' => true,
                'featured' => false,
                'amenities' => ['Grand Mosque', 'Theme Park', 'Golf Club', 'Hospital', 'Schools', 'Dancing Fountain', 'Eiffel Tower Replica'],
                'highlights' => ['Asia\'s largest private housing scheme', 'Multiple precincts for all budgets', '100% developed infrastructure', 'Award-winning town planning'],
                'plot_sizes' => ['125 Sqyd', '250 Sqyd', '500 Sqyd', '1000 Sqyd'],
                'payment_plan' => [],
            ],
            [
                'name' => 'Mall of Lahore',
                'slug' => 'mall-of-lahore',
                'category' => 'commercial-project',
                'developer' => 'Ittehad Developers',
                'location' => 'johar-town-lahore',
                'full_location' => 'Main Boulevard, Johar Town, Lahore',
                'status' => 'Launching Soon',
                'price_from' => 'Starting from PKR 55 Lac',
                'total_area' => '6 Kanal',
                'total_units' => 450,
                'description' => "A mixed-use commercial tower with shops, offices and food court.\nPrime location on Johar Town main boulevard.\nHigh-return investment opportunity.",
                'verified' => false,
                'featured' => false,
                'amenities' => ['Escalators', 'Central AC', 'Parking Plaza', 'Food Court', 'Kids Play Area', 'ATM Lobby'],
                'highlights' => ['LDA approved', 'Expected completion in 2027', '12% annual rental yield projected'],
                'plot_sizes' => [],
                'payment_plan' => [
                    ['label' => 'Booking', 'value' => '15%'],
                    ['label' => 'Monthly Installments', 'value' => '36 months'],
                    ['label' => 'On Possession', 'value' => '25%'],
                ],
            ],
            [
                'name' => 'DHA Quetta',
                'slug' => 'dha-quetta',
                'category' => 'residential-project',
                'developer' => 'DHA Quetta',
                'location' => 'rawalpindi',
                'full_location' => 'Kuchlak Road, Quetta, Balochistan, Pakistan',
                'status' => 'Under Construction',
                'price_from' => 'Starting from PKR 18 Lac',
                'total_area' => '10000 Kanal',
                'total_units' => 8000,
                'description' => "Defence Housing Authority's project in Quetta.\nModern living in the scenic capital of Balochistan.\nAffordable plots with easy installments.",
                'verified' => true,
                'featured' => false,
                'amenities' => ['Parks', 'Mosque', 'Hospital', 'School', 'Commercial Area'],
                'highlights' => ['GHQ approved', 'Wide roads with green belts', 'Affordable pricing for Balochistan'],
                'plot_sizes' => ['5 Marla', '10 Marla', '1 Kanal'],
                'payment_plan' => [
                    ['label' => 'Down Payment', 'value' => '20%'],
                    ['label' => 'Quarterly Installments', 'value' => '4 years'],
                    ['label' => 'On Possession', 'value' => '10%'],
                ],
            ],
        ];

        foreach ($projects as $data) {
            $categoryModel = $categories[$data['category']] ?? $categories->first();
            $locationModel = $locations[$data['location']] ?? $locations->first();

            $project = Project::create([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'project_category_id' => $categoryModel->id,
                'developer' => $data['developer'],
                'location_id' => $locationModel->id,
                'full_location' => $data['full_location'],
                'status' => $data['status'],
                'price_from' => $data['price_from'],
                'total_area' => $data['total_area'],
                'total_units' => $data['total_units'],
                'description' => $data['description'],
                'verified' => $data['verified'],
                'featured' => $data['featured'],
            ]);

            if (! empty($data['amenities'])) {
                $project->amenities()->createMany(
                    array_map(fn (string $name) => ['name' => $name], $data['amenities'])
                );
            }

            if (! empty($data['highlights'])) {
                $project->highlights()->createMany(
                    array_map(fn (string $text) => ['text' => $text], $data['highlights'])
                );
            }

            if (! empty($data['plot_sizes'])) {
                $project->plotSizes()->createMany(
                    array_map(fn (string $size) => ['size' => $size], $data['plot_sizes'])
                );
            }

            if (! empty($data['payment_plan'])) {
                foreach ($data['payment_plan'] as $i => $plan) {
                    $project->paymentPlans()->create([
                        'label' => $plan['label'],
                        'value' => $plan['value'],
                        'sort_order' => $i,
                    ]);
                }
            }
        }
    }
}
