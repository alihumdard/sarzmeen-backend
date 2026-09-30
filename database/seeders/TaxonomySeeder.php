<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\ProjectCategory;
use App\Models\PropertyType;
use Illuminate\Database\Seeder;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedPropertyTypes();
        $this->seedProjectCategories();
        $this->seedLocations();
    }

    private function seedCategories(): void
    {
        $topLevel = [
            ['name' => 'Houses', 'slug' => 'houses', 'description' => 'Independent houses, villas and bungalows.', 'featured' => true],
            ['name' => 'Flats & Apartments', 'slug' => 'flats-apartments', 'description' => 'Apartments, flats and penthouses in residential buildings.', 'featured' => true],
            ['name' => 'Plots', 'slug' => 'plots', 'description' => 'Residential, commercial and agricultural land.', 'featured' => true],
            ['name' => 'Commercial', 'slug' => 'commercial', 'description' => 'Shops, offices, warehouses and factories.', 'featured' => true],
            ['name' => 'Farm Houses', 'slug' => 'farm-houses', 'description' => 'Farmhouses and country properties on large plots.', 'status' => 'inactive'],
            ['name' => 'High Raise Projects', 'slug' => 'high-raise-projects', 'description' => 'Apartments, shops and food courts inside towers.'],
        ];

        foreach ($topLevel as $cat) {
            Category::create(array_merge(['status' => 'active', 'featured' => false], $cat));
        }

        $children = [
            ['name' => 'Upper Portion', 'slug' => 'upper-portion', 'description' => 'Upper floors let or sold separately from the main house.', 'parent' => 'houses'],
            ['name' => 'Lower Portion', 'slug' => 'lower-portion', 'description' => 'Ground floors let or sold separately from the main house.', 'parent' => 'houses'],
            ['name' => 'Residential Plot', 'slug' => 'residential-plot', 'description' => 'Plots approved for residential construction.', 'parent' => 'plots'],
            ['name' => 'Commercial Plot', 'slug' => 'commercial-plot', 'description' => 'Plots approved for shops, offices and plazas.', 'parent' => 'plots'],
            ['name' => 'Shops', 'slug' => 'shops', 'description' => 'Retail units on main roads and inside plazas.', 'parent' => 'commercial'],
            ['name' => 'Offices', 'slug' => 'offices', 'description' => 'Office floors and suites in commercial buildings.', 'parent' => 'commercial'],
        ];

        foreach ($children as $child) {
            $parentSlug = $child['parent'];
            unset($child['parent']);
            $parentId = Category::where('slug', $parentSlug)->value('id');
            Category::create(array_merge(['status' => 'active', 'featured' => false, 'parent_id' => $parentId], $child));
        }
    }

    private function seedPropertyTypes(): void
    {
        $types = [
            ['name' => 'House', 'slug' => 'house', 'description' => 'Independent houses on their own plot.', 'category' => 'houses', 'featured' => true],
            ['name' => 'Upper Portion', 'slug' => 'upper-portion', 'description' => 'Upper floor let or sold separately.', 'category' => 'houses'],
            ['name' => 'Lower Portion', 'slug' => 'lower-portion', 'description' => 'Ground floor let or sold separately.', 'category' => 'houses'],
            ['name' => 'Farm House', 'slug' => 'farm-house', 'description' => 'Country houses on large plots.', 'category' => 'houses'],
            ['name' => 'Flat', 'slug' => 'flat', 'description' => 'Apartments and flats in residential buildings.', 'category' => 'flats-apartments', 'featured' => true],
            ['name' => 'Penthouse', 'slug' => 'penthouse', 'description' => 'Top-floor apartments with private terraces.', 'category' => 'flats-apartments'],
            ['name' => 'Room', 'slug' => 'room', 'description' => 'Single rooms, usually let to students or professionals.', 'category' => 'flats-apartments'],
            ['name' => 'Residential Plot', 'slug' => 'residential-plot', 'description' => 'Plots approved for residential construction.', 'category' => 'plots', 'featured' => true],
            ['name' => 'Commercial Plot', 'slug' => 'commercial-plot', 'description' => 'Plots approved for shops, offices and plazas.', 'category' => 'plots'],
            ['name' => 'Agricultural Land', 'slug' => 'agricultural-land', 'description' => 'Farmland sold by acre or kanal.', 'category' => 'plots', 'status' => 'inactive'],
            ['name' => 'Shop', 'slug' => 'shop', 'description' => 'Retail units on main roads and inside plazas.', 'category' => 'commercial', 'featured' => true],
            ['name' => 'Office', 'slug' => 'office', 'description' => 'Office floors and suites in commercial buildings.', 'category' => 'commercial'],
            ['name' => 'Warehouse', 'slug' => 'warehouse', 'description' => 'Storage space with loading access.', 'category' => 'commercial'],
            ['name' => 'Food Court', 'slug' => 'food-court', 'description' => 'Food court units inside high-rise projects.', 'category' => 'high-raise-projects'],
        ];

        foreach ($types as $type) {
            $categorySlug = $type['category'];
            unset($type['category']);
            $categoryId = Category::where('slug', $categorySlug)->value('id');
            PropertyType::create(array_merge(['status' => 'active', 'featured' => false, 'category_id' => $categoryId], $type));
        }
    }

    private function seedProjectCategories(): void
    {
        $categories = [
            ['name' => 'Residential Project', 'slug' => 'residential-project', 'description' => 'Housing societies and residential developments sold plot by plot.', 'featured' => true],
            ['name' => 'Commercial Project', 'slug' => 'commercial-project', 'description' => 'Plazas, malls and office towers sold or let by unit.', 'featured' => true],
            ['name' => 'Mixed-Use Project', 'slug' => 'mixed-use-project', 'description' => 'Developments combining apartments, shops and offices in one tower.', 'featured' => true],
            ['name' => 'Farmhouse Society', 'slug' => 'farmhouse-society', 'description' => 'Gated farmhouse communities on the city outskirts.'],
            ['name' => 'Overseas Housing Scheme', 'slug' => 'overseas-housing-scheme', 'description' => 'Schemes reserved for overseas Pakistani buyers, with their own payment plans.'],
            ['name' => 'High Raise Project', 'slug' => 'high-raise-project', 'description' => 'Apartment towers with shops and food courts on lower floors.'],
            ['name' => 'Industrial Estate', 'slug' => 'industrial-estate', 'description' => 'Factory and warehouse plots in planned industrial zones.', 'status' => 'inactive'],
        ];

        foreach ($categories as $cat) {
            ProjectCategory::create(array_merge(['status' => 'active', 'featured' => false], $cat));
        }
    }

    private function seedLocations(): void
    {
        $cities = [
            ['name' => 'Lahore', 'slug' => 'lahore', 'latitude' => 31.5204000, 'longitude' => 74.3587000, 'featured' => true],
            ['name' => 'Islamabad', 'slug' => 'islamabad', 'latitude' => 33.6844000, 'longitude' => 73.0479000, 'featured' => true],
            ['name' => 'Karachi', 'slug' => 'karachi', 'latitude' => 24.8607000, 'longitude' => 67.0011000, 'featured' => true],
            ['name' => 'Rawalpindi', 'slug' => 'rawalpindi', 'latitude' => 33.5651000, 'longitude' => 73.0169000, 'featured' => true],
            ['name' => 'Faisalabad', 'slug' => 'faisalabad', 'latitude' => 31.4504000, 'longitude' => 73.1350000],
            ['name' => 'Multan', 'slug' => 'multan', 'latitude' => 30.1575000, 'longitude' => 71.5249000],
            ['name' => 'Peshawar', 'slug' => 'peshawar', 'latitude' => 34.0151000, 'longitude' => 71.5249000],
            ['name' => 'Gujranwala', 'slug' => 'gujranwala', 'latitude' => 32.1877000, 'longitude' => 74.1945000],
        ];

        foreach ($cities as $city) {
            Location::create(array_merge(['type' => 'city', 'status' => 'active', 'featured' => false], $city));
        }

        $lahoreId = Location::where('slug', 'lahore')->value('id');
        $islamabadId = Location::where('slug', 'islamabad')->value('id');
        $karachiId = Location::where('slug', 'karachi')->value('id');

        $areas = [
            ['name' => 'DHA Defence', 'slug' => 'dha-defence-lahore', 'parent_id' => $lahoreId, 'featured' => true],
            ['name' => 'Bahria Town', 'slug' => 'bahria-town-lahore', 'parent_id' => $lahoreId, 'featured' => true],
            ['name' => 'Gulberg', 'slug' => 'gulberg-lahore', 'parent_id' => $lahoreId],
            ['name' => 'Johar Town', 'slug' => 'johar-town-lahore', 'parent_id' => $lahoreId],
            ['name' => 'Model Town', 'slug' => 'model-town-lahore', 'parent_id' => $lahoreId],
            ['name' => 'DHA Islamabad', 'slug' => 'dha-islamabad', 'parent_id' => $islamabadId, 'featured' => true],
            ['name' => 'Bahria Town Islamabad', 'slug' => 'bahria-town-islamabad', 'parent_id' => $islamabadId, 'featured' => true],
            ['name' => 'E-11', 'slug' => 'e-11-islamabad', 'parent_id' => $islamabadId],
            ['name' => 'F-10', 'slug' => 'f-10-islamabad', 'parent_id' => $islamabadId],
            ['name' => 'DHA Karachi', 'slug' => 'dha-karachi', 'parent_id' => $karachiId, 'featured' => true],
            ['name' => 'Clifton', 'slug' => 'clifton-karachi', 'parent_id' => $karachiId],
            ['name' => 'Gulshan-e-Iqbal', 'slug' => 'gulshan-e-iqbal-karachi', 'parent_id' => $karachiId],
        ];

        foreach ($areas as $area) {
            Location::create(array_merge(['type' => 'area', 'status' => 'active', 'featured' => false], $area));
        }

        $dhaLahoreId = Location::where('slug', 'dha-defence-lahore')->value('id');
        $bahriaLahoreId = Location::where('slug', 'bahria-town-lahore')->value('id');

        $societies = [
            ['name' => 'DHA Phase 5', 'slug' => 'dha-phase-5-lahore', 'parent_id' => $dhaLahoreId],
            ['name' => 'DHA Phase 6', 'slug' => 'dha-phase-6-lahore', 'parent_id' => $dhaLahoreId],
            ['name' => 'DHA Phase 9 Prism', 'slug' => 'dha-phase-9-prism-lahore', 'parent_id' => $dhaLahoreId],
            ['name' => 'Bahria Orchard', 'slug' => 'bahria-orchard-lahore', 'parent_id' => $bahriaLahoreId],
            ['name' => 'Bahria Town Sector C', 'slug' => 'bahria-town-sector-c-lahore', 'parent_id' => $bahriaLahoreId],
        ];

        foreach ($societies as $society) {
            Location::create(array_merge(['type' => 'society', 'status' => 'active', 'featured' => false], $society));
        }
    }
}
