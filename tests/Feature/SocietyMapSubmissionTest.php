<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Models\SocietyMap;
use App\Models\SocietyMapSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Maps sent in by society owners, and their review.
 */
class SocietyMapSubmissionTest extends TestCase
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

    private function society(): Location
    {
        $city = Location::create([
            'name' => 'Lahore', 'slug' => 'lahore', 'type' => 'city', 'status' => 'active',
        ]);
        $area = Location::create([
            'parent_id' => $city->id, 'name' => 'Bahria Town', 'slug' => 'bt',
            'type' => 'area', 'status' => 'active',
        ]);

        return Location::create([
            'parent_id' => $area->id, 'name' => 'Bahria Orchard', 'slug' => 'bo',
            'type' => 'society', 'status' => 'active',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'societyName' => 'Green Valley Housing Scheme',
            'cityName' => 'Lahore',
            'nocApproved' => true,
            'nocAuthority' => 'LDA',
            'contactName' => 'Imran Shah',
            'contactPhone' => '03001234567',
            'contactEmail' => 'imran@example.com',
            'contactRole' => 'Owner',
            'notes' => 'Block-wise layout, approved 2025.',
            'imageFile' => UploadedFile::fake()->image('map.png', 2400, 1800),
        ], $overrides);
    }

    public function test_anybody_may_submit_a_map(): void
    {
        Storage::fake('public');

        /* Society offices and developers hold these maps and are not
           users of the site; requiring an account would mean never
           receiving one. */
        $response = $this->postJson('/api/society-map-submissions', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['message', 'reference']);

        $this->assertSame('SM-00001', $response->json('reference'));

        $submission = SocietyMapSubmission::firstOrFail();
        $this->assertSame('pending', $submission->status->value);
        $this->assertSame('Green Valley Housing Scheme', $submission->society_name);
        $this->assertTrue($submission->noc_approved);
        $this->assertNull($submission->user_id);
    }

    public function test_a_submitted_image_is_kept_at_full_size(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('map.png', 2400, 1800);
        $sent = $file->getSize();

        $this->postJson('/api/society-map-submissions', $this->payload(['imageFile' => $file]))
            ->assertCreated();

        $stored = SocietyMapSubmission::firstOrFail();

        /* An administrator should see exactly what was sent. */
        $this->assertSame($sent, Storage::disk('public')->size($stored->image));
    }

    public function test_a_map_image_is_required(): void
    {
        Storage::fake('public');

        $payload = $this->payload();
        unset($payload['imageFile']);

        $this->postJson('/api/society-map-submissions', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('imageFile');
    }

    public function test_a_contact_number_is_required(): void
    {
        Storage::fake('public');

        $this->postJson('/api/society-map-submissions', $this->payload(['contactPhone' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('contactPhone');
    }

    public function test_not_knowing_the_noc_status_is_allowed(): void
    {
        Storage::fake('public');

        /* Forcing a yes/no would only produce guesses. */
        $this->postJson('/api/society-map-submissions', $this->payload(['nocApproved' => null]))
            ->assertCreated();

        $this->assertNull(SocietyMapSubmission::firstOrFail()->noc_approved);
    }

    public function test_a_signed_in_sender_is_recorded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/society-map-submissions', $this->payload())
            ->assertCreated();

        $this->assertSame($user->id, SocietyMapSubmission::firstOrFail()->user_id);
    }

    public function test_a_submission_is_not_a_published_map(): void
    {
        Storage::fake('public');

        $this->postJson('/api/society-map-submissions', $this->payload())->assertCreated();

        /* Nothing reaches the public pages until an administrator says so. */
        $this->getJson('/api/society-maps')->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(0, SocietyMap::count());
    }

    public function test_an_administrator_sees_what_was_sent(): void
    {
        Storage::fake('public');

        $this->postJson('/api/society-map-submissions', $this->payload())->assertCreated();

        $this->actingAs($this->admin())
            ->getJson('/api/admin/society-map-submissions')
            ->assertOk()
            ->assertJsonPath('data.0.societyName', 'Green Valley Housing Scheme')
            ->assertJsonPath('data.0.contact.phone', '03001234567')
            ->assertJsonPath('data.0.nocApproved', true)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('meta.pending', 1);
    }

    public function test_approving_publishes_a_map(): void
    {
        Storage::fake('public');

        $society = $this->society();
        $this->postJson('/api/society-map-submissions', $this->payload())->assertCreated();
        $submission = SocietyMapSubmission::firstOrFail();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/society-map-submissions/{$submission->id}/approve", [
                'locationId' => $society->id,
                'title' => 'Bahria Orchard Lahore Map',
                'summary' => 'Block-wise layout.',
            ])
            ->assertOk()
            ->assertJsonPath('mapSlug', 'bahria-orchard-lahore-map');

        $map = SocietyMap::firstOrFail();
        $this->assertSame($society->id, $map->location_id);
        $this->assertSame('Lahore', $map->city?->name);

        $this->assertSame('published', $submission->fresh()->status->value);
        $this->assertSame($map->id, $submission->fresh()->society_map_id);

        /* It is now on the public pages. */
        $this->getJson('/api/society-maps')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_the_published_map_keeps_its_own_copy_of_the_file(): void
    {
        Storage::fake('public');

        $society = $this->society();
        $this->postJson('/api/society-map-submissions', $this->payload())->assertCreated();
        $submission = SocietyMapSubmission::firstOrFail();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/society-map-submissions/{$submission->id}/approve", [
                'locationId' => $society->id,
                'title' => 'Bahria Orchard Map',
            ])
            ->assertOk();

        $map = SocietyMap::firstOrFail();

        /* Two files, so the record of what was sent survives any later
           edit of the published map. */
        $this->assertNotSame($submission->image, $map->image);
        Storage::disk('public')->assertExists($submission->image);
        Storage::disk('public')->assertExists($map->image);
    }

    public function test_a_society_that_already_has_a_map_is_refused(): void
    {
        Storage::fake('public');

        $society = $this->society();
        SocietyMap::create([
            'location_id' => $society->id,
            'title' => 'Existing', 'slug' => 'existing',
            'image' => 'society-maps/x.png', 'status' => 'published',
            'published_at' => now(),
        ]);

        $this->postJson('/api/society-map-submissions', $this->payload())->assertCreated();
        $submission = SocietyMapSubmission::firstOrFail();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/society-map-submissions/{$submission->id}/approve", [
                'locationId' => $society->id,
                'title' => 'Another Map',
            ])
            ->assertStatus(422);

        /* Still waiting, not silently lost. */
        $this->assertSame('pending', $submission->fresh()->status->value);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        Storage::fake('public');

        $this->postJson('/api/society-map-submissions', $this->payload())->assertCreated();
        $submission = SocietyMapSubmission::firstOrFail();

        /* Somebody is waiting to hear why. */
        $this->actingAs($this->admin())
            ->postJson("/api/admin/society-map-submissions/{$submission->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('note');

        $this->actingAs($this->admin())
            ->postJson("/api/admin/society-map-submissions/{$submission->id}/reject", [
                'note' => 'The image is too small to read plot numbers.',
            ])
            ->assertOk();

        $this->assertSame('rejected', $submission->fresh()->status->value);
        $this->assertSame(0, SocietyMap::count());
    }

    public function test_a_submission_cannot_be_reviewed_twice(): void
    {
        Storage::fake('public');

        $this->postJson('/api/society-map-submissions', $this->payload())->assertCreated();
        $submission = SocietyMapSubmission::firstOrFail();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/society-map-submissions/{$submission->id}/reject", ['note' => 'No.'])
            ->assertOk();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/society-map-submissions/{$submission->id}/approve", [
                'locationId' => $this->society()->id,
                'title' => 'Too late',
            ])
            ->assertStatus(422);
    }

    public function test_a_visitor_cannot_review_submissions(): void
    {
        $this->getJson('/api/admin/society-map-submissions')->assertUnauthorized();
    }

    public function test_an_agency_cannot_review_submissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('agency', 'web'));

        $this->actingAs($user)
            ->getJson('/api/admin/society-map-submissions')
            ->assertForbidden();
    }
}
