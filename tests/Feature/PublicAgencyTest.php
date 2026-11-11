<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An agency's public profile: its team and what it has on the market.
 *
 * Both were counted but never returned, so the page showed "1 Team Member"
 * and "4 Active Listings" above empty space.
 */
class PublicAgencyTest extends TestCase
{
    use RefreshDatabase;

    private function agency(array $overrides = []): Agency
    {
        $owner = User::factory()->create();

        return Agency::create(array_merge([
            'user_id' => $owner->id,
            'name' => 'Royal Pak Properties',
            'slug' => 'royal-pak-properties',
            'status' => 'approved',
        ], $overrides));
    }

    private function agentFor(Agency $agency, string $name, string $slug): Agent
    {
        $user = User::factory()->create(['name' => $name]);

        return Agent::create([
            'user_id' => $user->id,
            'agency_id' => $agency->id,
            'slug' => $slug,
            'title' => 'Sales Agent',
            'status' => 'approved',
        ]);
    }

    private function propertyFor(Agency $agency, string $title, string $purpose): Property
    {
        return Property::create([
            'owner_type' => User::class,
            'owner_id' => $agency->user_id,
            'agency_id' => $agency->id,
            'title' => $title,
            /* Unique per row: the same title appears under two agencies. */
            'slug' => str($title)->slug()->value().'-'.str()->random(5),
            'reference' => 'SZ-'.str()->random(6),
            'property_type_id' => $this->propertyTypeId(),
            'location_id' => $this->locationId(),
            'purpose' => $purpose,
            'status' => 'published',
            'price' => 5_000_000,
            'area_value' => 10,
            'area_unit' => 'marla',
            'listed_by' => 'agency',
        ]);
    }

    /** One shared type and location; neither is what these tests are about. */
    private function propertyTypeId(): int
    {
        $category = Category::firstOrCreate(
            ['slug' => 'residential'],
            ['name' => 'Residential'],
        );

        return PropertyType::firstOrCreate(
            ['slug' => 'house'],
            ['name' => 'House', 'category_id' => $category->id],
        )->id;
    }

    private function locationId(): int
    {
        return Location::firstOrCreate(
            ['slug' => 'lahore'],
            ['name' => 'Lahore'],
        )->id;
    }

    public function test_the_profile_lists_the_agency_team(): void
    {
        $agency = $this->agency();
        $this->agentFor($agency, 'Haseeb Khokar', 'haseeb-khokar');

        $this->getJson("/api/agencies/{$agency->slug}")
            ->assertOk()
            ->assertJsonPath('data.totalAgents', 1)
            ->assertJsonCount(1, 'data.agents')
            ->assertJsonPath('data.agents.0.name', 'Haseeb Khokar')
            ->assertJsonPath('data.agents.0.slug', 'haseeb-khokar')
            ->assertJsonPath('data.agents.0.title', 'Sales Agent');
    }

    public function test_the_team_count_and_the_team_list_agree(): void
    {
        $agency = $this->agency();
        $this->agentFor($agency, 'One', 'one');
        $this->agentFor($agency, 'Two', 'two');

        $response = $this->getJson("/api/agencies/{$agency->slug}")->assertOk();

        $this->assertSame(
            $response->json('data.totalAgents'),
            count($response->json('data.agents')),
        );
    }

    public function test_the_profile_counts_listings_by_purpose(): void
    {
        $agency = $this->agency();
        $this->propertyFor($agency, 'House for sale', 'sale');
        $this->propertyFor($agency, 'Shop for sale', 'sale');
        $this->propertyFor($agency, 'Plot for rent', 'rent');

        $this->getJson("/api/agencies/{$agency->slug}")
            ->assertOk()
            ->assertJsonPath('data.propertiesForSale', 2)
            ->assertJsonPath('data.propertiesForRent', 1);
    }

    public function test_an_agencys_listings_can_be_fetched_by_its_slug(): void
    {
        $agency = $this->agency();
        $this->propertyFor($agency, 'House for sale', 'sale');
        $this->propertyFor($agency, 'Plot for rent', 'rent');

        /*
         * By slug, which is what the agency's own page has to hand. Only the
         * id worked before, so the page could not ask for its own listings.
         */
        $this->getJson("/api/properties?agency={$agency->slug}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_an_agencys_listings_can_still_be_fetched_by_id(): void
    {
        $agency = $this->agency();
        $this->propertyFor($agency, 'House for sale', 'sale');

        $this->getJson("/api/properties?agency={$agency->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_another_agencys_listings_are_not_included(): void
    {
        $mine = $this->agency();
        $theirs = $this->agency(['name' => 'Other', 'slug' => 'other-agency']);

        $this->propertyFor($mine, 'Mine', 'sale');
        $this->propertyFor($theirs, 'Theirs', 'sale');

        $this->getJson("/api/properties?agency={$mine->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mine');
    }
}
