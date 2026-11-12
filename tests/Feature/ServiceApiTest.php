<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $permission = Permission::findOrCreate('services.manage', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'new-service',
            'title' => 'New Service',
            'outcome' => 'A clear result',
            'summary' => 'What this service does.',
            'points' => ['First thing', 'Second thing'],
            'icon' => 'handshake',
            'status' => 'published',
            'featured' => false,
            'position' => 1,
        ], $overrides);
    }

    public function test_the_public_list_shows_only_published_services(): void
    {
        Service::create($this->payload(['slug' => 'shown', 'title' => 'Shown']));
        Service::create($this->payload([
            'slug' => 'hidden-one',
            'title' => 'Hidden One',
            'status' => 'hidden',
        ]));

        $response = $this->getJson('/api/services');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Shown');
    }

    public function test_the_public_list_orders_by_position(): void
    {
        Service::create($this->payload(['slug' => 'second', 'title' => 'Second', 'position' => 2]));
        Service::create($this->payload(['slug' => 'first', 'title' => 'First', 'position' => 1]));

        $this->getJson('/api/services')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'First')
            ->assertJsonPath('data.1.title', 'Second');
    }

    public function test_one_service_can_be_fetched_by_its_slug(): void
    {
        Service::create($this->payload(['slug' => 'single', 'title' => 'Single']));

        $this->getJson('/api/services/single')
            ->assertOk()
            ->assertJsonPath('data.title', 'Single')
            ->assertJsonPath('data.points.0', 'First thing');
    }

    public function test_a_hidden_service_has_no_public_page(): void
    {
        Service::create($this->payload(['slug' => 'secret', 'status' => 'hidden']));

        $this->getJson('/api/services/secret')->assertNotFound();
    }

    public function test_an_unknown_slug_is_not_found(): void
    {
        $this->getJson('/api/services/nothing-here')->assertNotFound();
    }

    public function test_an_admin_can_create_a_service(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/services', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.slug', 'new-service')
            ->assertJsonPath('data.points.1', 'Second thing');

        $this->assertDatabaseHas('services', ['slug' => 'new-service']);
    }

    public function test_an_admin_can_update_a_service_keeping_its_own_slug(): void
    {
        $service = Service::create($this->payload(['slug' => 'keep-me']));

        $this->actingAs($this->admin())
            ->putJson("/api/admin/services/{$service->id}", $this->payload([
                'slug' => 'keep-me',
                'title' => 'Renamed',
            ]))
            ->assertOk()
            ->assertJsonPath('data.title', 'Renamed');
    }

    public function test_a_duplicate_slug_is_refused(): void
    {
        Service::create($this->payload(['slug' => 'taken']));

        $this->actingAs($this->admin())
            ->postJson('/api/admin/services', $this->payload(['slug' => 'taken']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_a_service_needs_at_least_one_point(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/services', $this->payload(['points' => []]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('points');
    }

    public function test_an_unknown_icon_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/services', $this->payload(['icon' => 'rocket']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('icon');
    }

    public function test_an_admin_can_delete_a_service(): void
    {
        $service = Service::create($this->payload(['slug' => 'remove-me']));

        $this->actingAs($this->admin())
            ->deleteJson("/api/admin/services/{$service->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('services', ['slug' => 'remove-me']);
    }

    public function test_a_guest_cannot_manage_services(): void
    {
        $this->postJson('/api/admin/services', $this->payload())
            ->assertUnauthorized();
    }

    public function test_a_user_without_the_permission_cannot_manage_services(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/admin/services', $this->payload())
            ->assertForbidden();
    }

    /*
     * The summary is the short line over the photograph; the description is
     * the fuller text on the service's own page. They were one field, so a
     * description long enough to be worth writing filled the hero with it.
     */

    public function test_a_service_keeps_its_description(): void
    {
        Service::create($this->payload([
            'slug' => 'with-description',
            'description' => "First paragraph.\n\nSecond paragraph.",
        ]));

        $this->getJson('/api/services/with-description')
            ->assertOk()
            ->assertJsonPath('data.summary', 'What this service does.')
            ->assertJsonPath('data.description', "First paragraph.\n\nSecond paragraph.");
    }

    public function test_a_service_written_before_the_field_has_no_description(): void
    {
        // Every service that already exists is in this state.
        Service::create($this->payload(['slug' => 'older']));

        $this->getJson('/api/services/older')
            ->assertOk()
            ->assertJsonPath('data.summary', 'What this service does.')
            ->assertJsonPath('data.description', null);
    }

    public function test_an_admin_can_save_a_description(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/services', $this->payload([
                'slug' => 'saved',
                'description' => 'The longer account.',
            ]))
            ->assertCreated();

        $this->assertSame(
            'The longer account.',
            Service::where('slug', 'saved')->value('description'),
        );
    }

    public function test_a_description_is_optional(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/services', $this->payload(['slug' => 'no-description']))
            ->assertCreated();

        $this->assertNull(Service::where('slug', 'no-description')->value('description'));
    }
}
