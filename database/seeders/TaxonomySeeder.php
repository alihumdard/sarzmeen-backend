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

    /**
     * Four top-level categories only. Subcategories are property *types*, not
     * nested categories — the form's Category -> Type cascade depends on that
     * split, and config/property_schema.php keys its field groups off the type
     * slugs seeded below.
     */
    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Homes', 'slug' => 'homes', 'description' => 'Houses, villas, apartments and portions to live in.', 'featured' => true],
            ['name' => 'Plots', 'slug' => 'plots', 'description' => 'Residential, commercial, industrial and agricultural land.', 'featured' => true],
            ['name' => 'Commercial', 'slug' => 'commercial', 'description' => 'Shops, offices, factories, warehouses and plazas.', 'featured' => true],
            ['name' => 'High-Rise', 'slug' => 'high-rise', 'description' => 'Apartments and commercial units inside tower developments.', 'featured' => true],
        ];

        foreach ($categories as $cat) {
            Category::create(array_merge(['status' => 'active', 'featured' => false], $cat));
        }
    }

    private function seedPropertyTypes(): void
    {
        $types = [
            // Homes
            ['name' => 'House', 'slug' => 'house', 'description' => 'Independent houses on their own plot.', 'category' => 'homes', 'featured' => true],
            ['name' => 'Villa', 'slug' => 'villa', 'description' => 'Detached villas, often in gated communities.', 'category' => 'homes'],
            ['name' => 'Townhouse', 'slug' => 'townhouse', 'description' => 'Terraced or semi-detached townhouses.', 'category' => 'homes'],
            ['name' => 'Flat / Apartment', 'slug' => 'flat-apartment', 'description' => 'Apartments and flats in residential buildings.', 'category' => 'homes', 'featured' => true],
            ['name' => 'Penthouse', 'slug' => 'penthouse', 'description' => 'Top-floor apartments with private terraces.', 'category' => 'homes'],
            ['name' => 'Duplex', 'slug' => 'duplex', 'description' => 'Two-floor units joined by an internal staircase.', 'category' => 'homes'],
            ['name' => 'Upper Portion', 'slug' => 'upper-portion', 'description' => 'Upper floor let or sold separately.', 'category' => 'homes'],
            ['name' => 'Lower Portion', 'slug' => 'lower-portion', 'description' => 'Ground floor let or sold separately.', 'category' => 'homes'],
            ['name' => 'Farm House', 'slug' => 'farm-house', 'description' => 'Country houses on large plots.', 'category' => 'homes'],
            ['name' => 'Room', 'slug' => 'room', 'description' => 'Single rooms, usually let to students or professionals.', 'category' => 'homes'],

            // Plots
            ['name' => 'Residential Plot', 'slug' => 'residential-plot', 'description' => 'Plots approved for residential construction.', 'category' => 'plots', 'featured' => true],
            ['name' => 'Commercial Plot', 'slug' => 'commercial-plot', 'description' => 'Plots approved for shops, offices and plazas.', 'category' => 'plots'],
            ['name' => 'Industrial Land', 'slug' => 'industrial-land', 'description' => 'Land in planned industrial zones.', 'category' => 'plots'],
            ['name' => 'Agricultural Land', 'slug' => 'agricultural-land', 'description' => 'Farmland sold by acre or kanal.', 'category' => 'plots'],
            ['name' => 'Plot File', 'slug' => 'plot-file', 'description' => 'Allocation and balloting files for upcoming schemes.', 'category' => 'plots'],

            // Commercial
            ['name' => 'Shop', 'slug' => 'shop', 'description' => 'Retail units on main roads and inside plazas.', 'category' => 'commercial', 'featured' => true],
            ['name' => 'Office', 'slug' => 'office', 'description' => 'Office floors and suites in commercial buildings.', 'category' => 'commercial'],
            ['name' => 'Showroom', 'slug' => 'showroom', 'description' => 'Display showrooms with wide frontage.', 'category' => 'commercial'],
            ['name' => 'Factory', 'slug' => 'factory', 'description' => 'Manufacturing units with production and office areas.', 'category' => 'commercial'],
            ['name' => 'Warehouse', 'slug' => 'warehouse', 'description' => 'Storage space with loading access.', 'category' => 'commercial'],
            ['name' => 'Building', 'slug' => 'building', 'description' => 'Whole commercial buildings sold or let as one.', 'category' => 'commercial'],
            ['name' => 'Commercial Plaza', 'slug' => 'commercial-plaza', 'description' => 'Multi-storey plazas with shops and offices.', 'category' => 'commercial'],

            // High-Rise (residential)
            ['name' => 'Studio Apartment', 'slug' => 'studio-apartment', 'description' => 'Single-room apartments inside towers.', 'category' => 'high-rise', 'featured' => true],
            ['name' => '1-Bed Apartment', 'slug' => 'one-bed-apartment', 'description' => 'One-bedroom tower apartments.', 'category' => 'high-rise'],
            ['name' => '2-Bed Apartment', 'slug' => 'two-bed-apartment', 'description' => 'Two-bedroom tower apartments.', 'category' => 'high-rise', 'featured' => true],
            ['name' => '3-Bed Apartment', 'slug' => 'three-bed-apartment', 'description' => 'Three-bedroom tower apartments.', 'category' => 'high-rise'],
            ['name' => '4-Bed Apartment', 'slug' => 'four-bed-apartment', 'description' => 'Four-bedroom tower apartments.', 'category' => 'high-rise'],
            ['name' => 'Luxury Apartment', 'slug' => 'luxury-apartment', 'description' => 'Premium tower apartments with upgraded finishes.', 'category' => 'high-rise'],
            ['name' => 'Penthouse', 'slug' => 'high-rise-penthouse', 'description' => 'Top-floor penthouses inside a tower development.', 'category' => 'high-rise'],
            ['name' => 'Duplex Penthouse', 'slug' => 'duplex-penthouse', 'description' => 'Two-floor penthouses at the top of a tower.', 'category' => 'high-rise'],
            ['name' => 'Serviced Apartment', 'slug' => 'serviced-apartment', 'description' => 'Managed apartments let with services included.', 'category' => 'high-rise'],
            ['name' => 'Hotel Apartment', 'slug' => 'hotel-apartment', 'description' => 'Hotel-operated apartment units.', 'category' => 'high-rise'],

            // High-Rise (commercial)
            ['name' => 'High-Rise Shop', 'slug' => 'high-rise-shop', 'description' => 'Retail units inside tower developments.', 'category' => 'high-rise'],
            ['name' => 'High-Rise Office', 'slug' => 'high-rise-office', 'description' => 'Office units inside tower developments.', 'category' => 'high-rise'],
            ['name' => 'Showroom', 'slug' => 'high-rise-showroom', 'description' => 'Display showrooms inside tower developments.', 'category' => 'high-rise'],
            ['name' => 'Corporate Office', 'slug' => 'corporate-office', 'description' => 'Full corporate floors in office towers.', 'category' => 'high-rise'],
            ['name' => 'Food Court', 'slug' => 'food-court', 'description' => 'Food court units inside high-rise projects.', 'category' => 'high-rise'],
            ['name' => 'Restaurant', 'slug' => 'restaurant', 'description' => 'Restaurant spaces inside towers and malls.', 'category' => 'high-rise'],
            ['name' => 'Commercial Floor', 'slug' => 'commercial-floor', 'description' => 'Entire commercial floors in a tower.', 'category' => 'high-rise'],
            ['name' => 'Commercial Unit', 'slug' => 'commercial-unit', 'description' => 'General commercial units inside towers.', 'category' => 'high-rise'],
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
