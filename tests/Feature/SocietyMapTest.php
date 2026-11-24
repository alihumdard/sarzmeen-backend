<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Models\SocietyMap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Society maps: an administrator's pages, grouped by city.
 */
class SocietyMapTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $permission = Permission::findOrCreate('society_maps.manage', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** A city, an area beneath it, and a society beneath that. */
    private function society(string $city = 'Lahore', string $name = 'Bahria Town'): Location
    {
        $cityRow = Location::create([
            'name' => $city,
            'slug' => str($city)->slug()->toString(),
            'type' => 'city',
            'status' => 'active',
        ]);

        $area = Location::create([
            'parent_id' => $cityRow->id,
            'name' => $name.' Area',
            'slug' => str($name.' area')->slug()->toString(),
            'type' => 'area',
            'status' => 'active',
        ]);

        return Location::create([
            'parent_id' => $area->id,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'type' => 'society',
            'status' => 'active',
        ]);
    }

    private function map(Location $society, array $overrides = []): SocietyMap
    {
        return SocietyMap::create(array_merge([
            'location_id' => $society->id,
            'city_id' => SocietyMap::resolveCity($society)?->id,
            'title' => $society->name.' Map',
            'slug' => SocietyMap::uniqueSlug($society->name.' Map'),
            'image' => 'society-maps/test.png',
            'summary' => 'The society map.',
            'description' => '<p>Blocks and plot sizes.</p>',
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }

    public function test_the_city_is_resolved_through_the_area_above_the_society(): void
    {
        $society = $this->society();

        /* A society sits two levels under its city, so a parent lookup
           alone would find the area and call it a city. */
        $this->assertSame('Lahore', SocietyMap::resolveCity($society)?->name);
    }

    public function test_only_cities_holding_a_published_map_are_listed(): void
    {
        $this->map($this->society('Lahore', 'Bahria Town'));
        $this->map($this->society('Karachi', 'Gulshan'), ['status' => 'draft', 'published_at' => null]);

        $response = $this->getJson('/api/society-maps/cities')->assertOk();

        $names = array_column($response->json('data'), 'name');

        $this->assertContains('Lahore', $names);
        $this->assertNotContains('Karachi', $names, 'A city whose only map is a draft leads to an empty page.');
    }

    public function test_a_city_counts_its_maps(): void
    {
        $city = $this->society('Lahore', 'Bahria Town');
        $this->map($city);
        $this->map($this->societyUnder($city->parent->parent, 'DHA'));

        $response = $this->getJson('/api/society-maps/cities')->assertOk();

        $this->assertSame(2, $response->json('data.0.mapCount'));
    }

    /** A second society under an existing city. */
    private function societyUnder(Location $city, string $name): Location
    {
        $area = Location::create([
            'parent_id' => $city->id,
            'name' => $name.' Area',
            'slug' => str($name.' area')->slug()->toString(),
            'type' => 'area',
            'status' => 'active',
        ]);

        return Location::create([
            'parent_id' => $area->id,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'type' => 'society',
            'status' => 'active',
        ]);
    }

    public function test_the_listing_can_be_filtered_by_city(): void
    {
        $this->map($this->society('Lahore', 'Bahria Town'));
        $this->map($this->society('Karachi', 'Gulshan'));

        $response = $this->getJson('/api/society-maps?city=lahore')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Bahria Town Map', $response->json('data.0.title'));
    }

    public function test_a_draft_map_stays_off_the_public_pages(): void
    {
        $map = $this->map($this->society(), ['status' => 'draft', 'published_at' => null]);

        $this->getJson('/api/society-maps')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/society-maps/{$map->slug}")->assertNotFound();
    }

    public function test_a_map_carries_its_society_and_city(): void
    {
        $map = $this->map($this->society());

        $response = $this->getJson("/api/society-maps/{$map->slug}")->assertOk();

        $this->assertSame('Bahria Town', $response->json('data.society.name'));
        $this->assertSame('Lahore', $response->json('data.city.name'));
    }

    public function test_the_listing_leaves_out_the_write_up(): void
    {
        $this->map($this->society());

        $response = $this->getJson('/api/society-maps')->assertOk();

        /* Thirty maps would otherwise carry thirty articles of HTML. */
        $this->assertArrayNotHasKey('description', $response->json('data.0'));
    }

    public function test_the_map_page_carries_the_write_up(): void
    {
        $map = $this->map($this->society());

        $this->getJson("/api/society-maps/{$map->slug}")
            ->assertOk()
            ->assertJsonPath('data.description', '<p>Blocks and plot sizes.</p>');
    }

    public function test_the_download_falls_back_to_the_image_when_there_is_no_pdf(): void
    {
        $map = $this->map($this->society());

        $response = $this->getJson("/api/society-maps/{$map->slug}")->assertOk();

        $this->assertFalse($response->json('data.hasDocument'));
        $this->assertSame(
            $response->json('data.image'),
            $response->json('data.download'),
            'Without a PDF the button must still hand over something.',
        );
    }

    public function test_a_pdf_is_preferred_for_the_download(): void
    {
        $map = $this->map($this->society(), ['document' => 'society-maps/plan.pdf']);

        $response = $this->getJson("/api/society-maps/{$map->slug}")->assertOk();

        $this->assertTrue($response->json('data.hasDocument'));
        $this->assertStringContainsString('plan.pdf', $response->json('data.download'));
    }

    public function test_a_download_is_counted(): void
    {
        $map = $this->map($this->society());

        $this->postJson("/api/society-maps/{$map->slug}/download")
            ->assertOk()
            ->assertJsonStructure(['url', 'filename']);

        $this->assertSame(1, $map->fresh()->downloads);
    }

    public function test_the_download_keeps_the_uploaded_file_type(): void
    {
        $map = $this->map($this->society(), ['image' => 'society-maps/test.jpg']);

        /* Naming a JPG ".png" makes a file some viewers refuse. */
        $this->postJson("/api/society-maps/{$map->slug}/download")
            ->assertOk()
            ->assertJsonPath('filename', $map->slug.'.jpg');
    }

    public function test_visiting_a_map_counts_a_view(): void
    {
        $map = $this->map($this->society());

        $this->getJson("/api/society-maps/{$map->slug}")->assertOk();

        $this->assertSame(1, $map->fresh()->views);
    }

    public function test_an_administrator_creates_a_map(): void
    {
        Storage::fake('public');

        $society = $this->society();

        $response = $this->actingAs($this->admin())
            ->postJson('/api/admin/society-maps', [
                'title' => 'Bahria Town Lahore Map',
                'locationId' => $society->id,
                'summary' => 'Sectors and blocks.',
                'description' => '<p>Everything about it.</p>',
                'status' => 'published',
                'imageFile' => UploadedFile::fake()->image('map.png', 2400, 1800),
            ])
            ->assertCreated();

        $this->assertSame('Bahria Town Lahore Map', $response->json('data.title'));
        $this->assertSame('Lahore', $response->json('data.city.name'));
    }

    public function test_a_society_may_only_have_one_map(): void
    {
        Storage::fake('public');

        $society = $this->society();
        $this->map($society);

        $this->actingAs($this->admin())
            ->postJson('/api/admin/society-maps', [
                'title' => 'Another Map',
                'locationId' => $society->id,
                'status' => 'draft',
                'imageFile' => UploadedFile::fake()->image('map.png', 2400, 1800),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('locationId');
    }

    public function test_renaming_a_map_keeps_its_url(): void
    {
        Storage::fake('public');

        $map = $this->map($this->society());
        $slug = $map->slug;

        $this->actingAs($this->admin())
            ->putJson("/api/admin/society-maps/{$map->id}", [
                'title' => 'A Much Better Title',
                'status' => 'published',
            ])
            ->assertOk();

        /* The slug is the published URL; renaming must not break links
           already shared. */
        $this->assertSame($slug, $map->fresh()->slug);
    }

    public function test_the_write_up_is_sanitised(): void
    {
        Storage::fake('public');

        $society = $this->society();

        $this->actingAs($this->admin())
            ->postJson('/api/admin/society-maps', [
                'title' => 'Scripted Map',
                'locationId' => $society->id,
                'description' => '<p>Fine</p><script>alert(1)</script>',
                'status' => 'draft',
                'imageFile' => UploadedFile::fake()->image('map.png', 2400, 1800),
            ])
            ->assertCreated();

        $stored = SocietyMap::where('title', 'Scripted Map')->firstOrFail();

        $this->assertStringNotContainsString('<script>', $stored->description);
        $this->assertStringContainsString('Fine', $stored->description);
    }

    public function test_blogs_are_attached_and_come_back_on_the_page(): void
    {
        Storage::fake('public');

        $society = $this->society();
        $map = $this->map($society);

        $blog = \App\Models\Blog::create([
            'title' => 'Bahria Town price trends',
            'slug' => 'bahria-town-price-trends',
            'excerpt' => 'What prices did this year.',
            'content' => '<p>Up.</p>',
            'status' => 'published',
            'author_id' => $this->admin()->id,
            'published_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/society-maps/{$map->id}", [
                'title' => $map->title,
                'status' => 'published',
                'blogIds' => [$blog->id],
            ])
            ->assertOk();

        /* The editor reads them back to show what is already attached. */
        $this->actingAs($this->admin())
            ->getJson("/api/admin/society-maps/{$map->id}")
            ->assertOk()
            ->assertJsonPath('data.blogs.0.title', 'Bahria Town price trends');

        $this->getJson("/api/society-maps/{$map->slug}")
            ->assertOk()
            ->assertJsonPath('data.blogs.0.title', 'Bahria Town price trends');
    }

    public function test_clearing_every_blog_detaches_them(): void
    {
        Storage::fake('public');

        $map = $this->map($this->society());
        $blog = \App\Models\Blog::create([
            'title' => 'A post',
            'slug' => 'a-post',
            'excerpt' => 'x',
            'content' => '<p>x</p>',
            'status' => 'published',
            'author_id' => $this->admin()->id,
            'published_at' => now(),
        ]);
        $map->blogs()->sync([$blog->id]);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/society-maps/{$map->id}", [
                'title' => $map->title,
                'status' => 'published',
                'blogIds' => [],
            ])
            ->assertOk();

        $this->assertCount(0, $map->fresh()->blogs);
    }

    public function test_a_visitor_cannot_manage_maps(): void
    {
        $this->postJson('/api/admin/society-maps', [])->assertUnauthorized();
    }

    public function test_an_agency_cannot_manage_maps(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('agency', 'web'));

        $this->actingAs($user)
            ->postJson('/api/admin/society-maps', [])
            ->assertForbidden();
    }
}
