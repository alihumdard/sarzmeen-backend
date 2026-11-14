<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\PropertyViewLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bringing seeded view counts down to what was logged.
 *
 * The counts were seeded when the site was built, so the dashboard
 * counted visits that never happened — and showed a second figure, from
 * the logs, that disagreed with it on the same screen.
 */
class ResetSeededViewsTest extends TestCase
{
    use RefreshDatabase;

    private function property(int $views): Property
    {
        $category = Category::firstOrCreate(['slug' => 'residential'], ['name' => 'Residential']);
        $type = PropertyType::firstOrCreate(
            ['slug' => 'house'],
            ['name' => 'House', 'category_id' => $category->id],
        );
        $location = Location::firstOrCreate(['slug' => 'lahore'], ['name' => 'Lahore']);
        $owner = User::factory()->create();

        return Property::create([
            'owner_type' => User::class,
            'owner_id' => $owner->id,
            'title' => 'A house',
            'slug' => 'a-house-'.str()->random(6),
            'reference' => 'SZ-'.str()->random(6),
            'property_type_id' => $type->id,
            'location_id' => $location->id,
            'purpose' => 'sale',
            'status' => 'published',
            'price' => 1_000_000,
            'area_value' => 5,
            'area_unit' => 'marla',
            'listed_by' => 'owner',
            'views' => $views,
        ]);
    }

    private function logViews(Property $property, int $count): void
    {
        PropertyViewLog::create([
            'property_id' => $property->id,
            'date' => now()->toDateString(),
            'count' => $count,
        ]);
    }

    public function test_a_seeded_count_comes_down_to_the_logged_one(): void
    {
        $property = $this->property(1955);
        $this->logViews($property, 38);

        $this->artisan('views:reset-seeded')
            ->expectsConfirmation('Lower 1 properties to their logged counts?', 'yes')
            ->assertSuccessful();

        $this->assertSame(38, $property->fresh()->views);
    }

    public function test_a_property_with_no_logs_falls_to_zero(): void
    {
        $property = $this->property(500);

        $this->artisan('views:reset-seeded')
            ->expectsConfirmation('Lower 1 properties to their logged counts?', 'yes')
            ->assertSuccessful();

        $this->assertSame(0, $property->fresh()->views);
    }

    /** Never raises a count: a real tally is not rewritten upwards. */
    public function test_a_count_below_its_log_is_left_alone(): void
    {
        $property = $this->property(10);
        $this->logViews($property, 40);

        $this->artisan('views:reset-seeded')
            ->expectsConfirmation('Lower 0 properties to their logged counts?', 'yes')
            ->assertSuccessful();

        $this->assertSame(10, $property->fresh()->views);
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $property = $this->property(1955);
        $this->logViews($property, 38);

        $this->artisan('views:reset-seeded', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(1955, $property->fresh()->views);
    }

    public function test_declining_the_confirmation_writes_nothing(): void
    {
        $property = $this->property(1955);
        $this->logViews($property, 38);

        $this->artisan('views:reset-seeded')
            ->expectsConfirmation('Lower 1 properties to their logged counts?', 'no')
            ->assertSuccessful();

        $this->assertSame(1955, $property->fresh()->views);
    }
}
