<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PropertyStatus;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sarzameen.com')->first();
        $types = PropertyType::all()->keyBy('slug');
        $locations = Location::all()->keyBy('slug');

        $listings = [
            [
                'title' => '10 Marla Brand New House in DHA Phase 6',
                'description' => "Beautifully designed 10 Marla house with modern architecture.\nPrime location near commercial area.\nAll utilities available.",
                'type' => 'house', 'location' => 'dha-phase-6-lahore', 'full_location' => 'DHA Phase 6, Lahore',
                'purpose' => 'sale', 'price' => 45000000, 'area_value' => 10, 'area_unit' => 'marla',
                'beds' => 5, 'baths' => 6, 'living_rooms' => 2, 'kitchens' => 1, 'car_parking' => 2, 'floors' => 2,
                'furnishing' => 'semi', 'condition' => 'ready', 'featured' => true, 'verified' => true,
                'features' => ['Lawn', 'Garage', 'CCTV', 'Servant Quarter', 'Electricity Backup'],
                'nearby' => [['name' => 'Y Block Commercial', 'distance' => '450 m', 'kind' => 'mall'], ['name' => 'DHA Main Boulevard', 'distance' => '200 m', 'kind' => 'road']],
            ],
            [
                'title' => '1 Kanal Luxury Villa in Bahria Town',
                'description' => "Stunning 1 Kanal villa with premium finishes.\nSwimming pool and landscaped garden.\nIdeal for families.",
                'type' => 'house', 'location' => 'bahria-town-lahore', 'full_location' => 'Bahria Town, Lahore',
                'purpose' => 'sale', 'price' => 85000000, 'area_value' => 1, 'area_unit' => 'kanal',
                'beds' => 7, 'baths' => 8, 'living_rooms' => 3, 'kitchens' => 2, 'car_parking' => 3, 'floors' => 3,
                'furnishing' => 'furnished', 'condition' => 'ready', 'featured' => true, 'verified' => true,
                'features' => ['Swimming Pool', 'Home Theater', 'Central AC', 'Solar Panels', 'Smart Home System'],
                'nearby' => [['name' => 'Bahria Grand Mosque', 'distance' => '1.2 km', 'kind' => 'park']],
            ],
            [
                'title' => '3 Bed Flat for Rent in Gulberg',
                'description' => "Well-maintained 3 bedroom apartment.\nClose to MM Alam Road restaurants.\nLift and generator available.",
                'type' => 'flat', 'location' => 'gulberg-lahore', 'full_location' => 'Gulberg III, Lahore',
                'purpose' => 'rent', 'price' => 120000, 'area_value' => 1800, 'area_unit' => 'sqft',
                'beds' => 3, 'baths' => 3, 'living_rooms' => 1, 'kitchens' => 1, 'car_parking' => 1, 'floors' => 1,
                'furnishing' => 'unfurnished', 'condition' => 'ready', 'featured' => false, 'verified' => true,
                'features' => ['Lift', 'Generator', 'Security', 'Intercom'],
                'nearby' => [['name' => 'MM Alam Road', 'distance' => '300 m', 'kind' => 'road']],
            ],
            [
                'title' => '5 Marla Residential Plot in DHA Phase 9 Prism',
                'description' => "Ideal investment opportunity in DHA Phase 9 Prism.\nPlot on 40 feet road.\nAll dues cleared.",
                'type' => 'residential-plot', 'location' => 'dha-phase-9-prism-lahore', 'full_location' => 'DHA Phase 9 Prism, Lahore',
                'purpose' => 'sale', 'price' => 12500000, 'area_value' => 5, 'area_unit' => 'marla',
                'beds' => null, 'baths' => null, 'living_rooms' => null, 'kitchens' => null, 'car_parking' => null, 'floors' => null,
                'furnishing' => null, 'condition' => null, 'featured' => true, 'verified' => false,
                'features' => [],
                'nearby' => [['name' => 'DHA Raya Golf Club', 'distance' => '2.5 km', 'kind' => 'park']],
            ],
            [
                'title' => 'Shop for Sale on Main Boulevard Johar Town',
                'description' => "Prime commercial shop facing main boulevard.\nHigh footfall area.\nSuitable for brand outlet.",
                'type' => 'shop', 'location' => 'johar-town-lahore', 'full_location' => 'Main Boulevard, Johar Town, Lahore',
                'purpose' => 'sale', 'price' => 32000000, 'area_value' => 600, 'area_unit' => 'sqft',
                'beds' => null, 'baths' => 1, 'living_rooms' => null, 'kitchens' => null, 'car_parking' => 1, 'floors' => 1,
                'furnishing' => null, 'condition' => 'ready', 'featured' => false, 'verified' => true,
                'features' => ['Main Road Facing', 'Corner Shop', 'Parking Space'],
                'nearby' => [['name' => 'Emporium Mall', 'distance' => '1.5 km', 'kind' => 'mall']],
            ],
            [
                'title' => 'Upper Portion for Rent in Model Town',
                'description' => "Spacious upper portion in Model Town.\n3 bedrooms with attached baths.\nIndependent entrance.",
                'type' => 'upper-portion', 'location' => 'model-town-lahore', 'full_location' => 'Model Town, Lahore',
                'purpose' => 'rent', 'price' => 85000, 'area_value' => 10, 'area_unit' => 'marla',
                'beds' => 3, 'baths' => 3, 'living_rooms' => 1, 'kitchens' => 1, 'car_parking' => 1, 'floors' => 1,
                'furnishing' => 'unfurnished', 'condition' => 'available_now', 'featured' => false, 'verified' => false,
                'features' => ['Independent Entrance', 'Rooftop Access', 'Sui Gas'],
                'nearby' => [['name' => 'Model Town Park', 'distance' => '600 m', 'kind' => 'park']],
            ],
            [
                'title' => 'Penthouse in DHA Islamabad',
                'description' => "Luxurious penthouse with panoramic views.\nRooftop terrace with jacuzzi.\nSmart home automation.",
                'type' => 'penthouse', 'location' => 'dha-islamabad', 'full_location' => 'DHA Phase 2, Islamabad',
                'purpose' => 'sale', 'price' => 120000000, 'area_value' => 4500, 'area_unit' => 'sqft',
                'beds' => 4, 'baths' => 5, 'living_rooms' => 2, 'kitchens' => 1, 'car_parking' => 2, 'floors' => 2,
                'furnishing' => 'furnished', 'condition' => 'ready', 'featured' => true, 'verified' => true,
                'features' => ['Rooftop Terrace', 'Jacuzzi', 'Smart Home', 'Central Heating', 'Panoramic Views'],
                'nearby' => [['name' => 'Islamabad Airport', 'distance' => '15 km', 'kind' => 'airport']],
            ],
            [
                'title' => 'Office Space in Clifton Karachi',
                'description' => "Modern office space in prime Clifton location.\nFully fitted with workstations.\nReady to move in.",
                'type' => 'office', 'location' => 'clifton-karachi', 'full_location' => 'Block 5, Clifton, Karachi',
                'purpose' => 'rent', 'price' => 250000, 'area_value' => 2200, 'area_unit' => 'sqft',
                'beds' => null, 'baths' => 2, 'living_rooms' => null, 'kitchens' => 1, 'car_parking' => 3, 'floors' => 1,
                'furnishing' => 'furnished', 'condition' => 'ready', 'featured' => false, 'verified' => true,
                'features' => ['Conference Room', 'Pantry', 'High Speed Internet', 'Generator Backup'],
                'nearby' => [['name' => 'Dolmen Mall Clifton', 'distance' => '800 m', 'kind' => 'mall']],
            ],
        ];

        foreach ($listings as $i => $data) {
            $typeModel = $types[$data['type']] ?? $types->first();
            $locationModel = $locations[$data['location']] ?? $locations->first();

            $property = Property::create([
                'slug' => Str::slug($data['title']) . '-' . Str::random(6),
                'reference' => 'SZ-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'title' => $data['title'],
                'headline' => $data['title'],
                'description' => $data['description'],
                'owner_id' => $admin->id,
                'owner_type' => User::class,
                'agency_id' => null,
                'property_type_id' => $typeModel->id,
                'location_id' => $locationModel->id,
                'full_location' => $data['full_location'],
                'purpose' => $data['purpose'],
                'price' => $data['price'],
                'negotiable' => true,
                'area_value' => $data['area_value'],
                'area_unit' => $data['area_unit'],
                'beds' => $data['beds'],
                'baths' => $data['baths'],
                'living_rooms' => $data['living_rooms'],
                'kitchens' => $data['kitchens'],
                'car_parking' => $data['car_parking'],
                'floors' => $data['floors'],
                'furnishing' => $data['furnishing'],
                'property_condition' => $data['condition'],
                'listed_by' => 'owner',
                'status' => PropertyStatus::Published,
                'featured' => $data['featured'],
                'verified' => $data['verified'],
                'views' => rand(50, 2000),
                'published_at' => now()->subDays(rand(1, 30)),
            ]);

            if (! empty($data['features'])) {
                $features = array_map(fn (string $name) => ['name' => $name], $data['features']);
                $property->features()->createMany($features);
            }

            if (! empty($data['nearby'])) {
                $property->nearbyPlaces()->createMany($data['nearby']);
            }
        }
    }
}
