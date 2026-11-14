<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Videos belonging to an agent or an agency.
 *
 * The point of the feature is the separation: these belong on the owner's
 * profile and must never reach the public library, which is a curated page
 * rather than a feed of everyone's listings.
 */
class OwnerVideoTest extends TestCase
{
    use RefreshDatabase;

    private const LINK = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

    private function admin(): User
    {
        $permission = \Spatie\Permission\Models\Permission::findOrCreate('blogs.manage', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function agent(): Agent
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('agent', 'web'));

        return Agent::create([
            'user_id' => $user->id,
            'slug' => 'an-agent-'.str()->random(5),
            'title' => 'Sales Agent',
            'status' => 'approved',
        ]);
    }

    private function agency(): Agency
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('agency', 'web'));

        return Agency::create([
            'user_id' => $user->id,
            'name' => 'An Agency',
            'slug' => 'an-agency-'.str()->random(5),
            'status' => 'approved',
        ]);
    }

    /** An editorial video: the site's own, with no owner. */
    private function editorial(string $title = 'Editorial'): Video
    {
        return Video::create([
            'title' => $title,
            'slug' => str($title)->slug()->value(),
            'youtube_url' => self::LINK,
            'youtube_id' => 'dQw4w9WgXcQ',
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function test_an_agent_adds_a_video(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', [
                'title' => 'A walk through the house',
                'youtubeUrl' => self::LINK,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'A walk through the house')
            ->assertJsonPath('data.youtubeId', 'dQw4w9WgXcQ');

        $this->assertSame(1, Video::where('agent_id', $agent->id)->count());
    }

    public function test_an_agency_adds_a_video(): void
    {
        $agency = $this->agency();

        $this->actingAs($agency->owner)
            ->postJson('/api/agency/videos', [
                'title' => 'Our new tower',
                'youtubeUrl' => self::LINK,
            ])
            ->assertCreated();

        $this->assertSame(1, Video::where('agency_id', $agency->id)->count());
    }

    /*
     * The separation, from both directions.
     */

    public function test_an_owners_video_stays_out_of_the_library(): void
    {
        $agent = $this->agent();
        $this->editorial('A site video');

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->assertCreated();

        $this->getJson('/api/videos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'A site video');
    }

    public function test_an_owners_video_cannot_be_opened_in_the_library_by_its_slug(): void
    {
        $agent = $this->agent();

        $slug = $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->json('data.slug');

        /* The list is not enough on its own: the slug route is the other
           door into the same page. */
        $this->getJson("/api/videos/{$slug}")->assertNotFound();
    }

    public function test_an_owners_video_is_not_offered_as_related(): void
    {
        $agent = $this->agent();
        $this->editorial('A site video');

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->assertCreated();

        $this->getJson('/api/videos/a-site-video/related')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_the_agents_page_shows_their_videos(): void
    {
        $agent = $this->agent();
        $this->editorial('A site video');

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->assertCreated();

        /* Theirs, and only theirs: the editorial one is not swept in. */
        $this->getJson("/api/agents/{$agent->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data.videos')
            ->assertJsonPath('data.videos.0.title', 'Mine');
    }

    public function test_the_agencys_page_shows_its_videos(): void
    {
        $agency = $this->agency();

        $this->actingAs($agency->owner)
            ->postJson('/api/agency/videos', ['title' => 'Ours', 'youtubeUrl' => self::LINK])
            ->assertCreated();

        $this->getJson("/api/agencies/{$agency->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data.videos')
            ->assertJsonPath('data.videos.0.title', 'Ours');
    }

    /*
     * Ownership.
     */

    public function test_an_agent_sees_only_their_own_videos(): void
    {
        $mine = $this->agent();
        $theirs = $this->agent();

        $this->actingAs($mine->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK]);
        $this->actingAs($theirs->user)
            ->postJson('/api/agent/videos', ['title' => 'Theirs', 'youtubeUrl' => self::LINK]);

        $this->actingAs($mine->user)
            ->getJson('/api/agent/videos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mine');
    }

    public function test_an_agent_cannot_delete_someone_elses_video(): void
    {
        $mine = $this->agent();
        $theirs = $this->agent();

        $id = $this->actingAs($theirs->user)
            ->postJson('/api/agent/videos', ['title' => 'Theirs', 'youtubeUrl' => self::LINK])
            ->json('data.id');

        $this->actingAs($mine->user)
            ->deleteJson("/api/agent/videos/{$id}")
            ->assertNotFound();

        $this->assertNotNull(Video::find($id));
    }

    public function test_an_agent_cannot_touch_an_editorial_video(): void
    {
        $agent = $this->agent();
        $video = $this->editorial();

        $this->actingAs($agent->user)
            ->deleteJson("/api/agent/videos/{$video->id}")
            ->assertNotFound();

        $this->assertNotNull(Video::find($video->id));
    }

    /*
     * The link itself.
     */

    public function test_a_link_that_is_not_youtube_is_refused(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', [
                'title' => 'Mine',
                'youtubeUrl' => 'https://example.com/not-a-video',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('youtubeUrl');
    }

    /** A share link, an embed and a bare id all carry the same id. */
    public function test_any_shape_of_youtube_link_is_accepted(): void
    {
        $agent = $this->agent();

        foreach ([
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'dQw4w9WgXcQ',
        ] as $i => $link) {
            $this->actingAs($agent->user)
                ->postJson('/api/agent/videos', ['title' => "Video {$i}", 'youtubeUrl' => $link])
                ->assertCreated()
                ->assertJsonPath('data.youtubeId', 'dQw4w9WgXcQ');
        }
    }

    /*
     * Who posted it. In the admin list a guide the site wrote and a
     * walkthrough an agency posted looked identical, so an administrator
     * could not tell which of them was theirs to edit.
     */

    public function test_the_admin_list_names_the_agent_behind_a_video(): void
    {
        $agent = $this->agent();
        $agent->user->update(['name' => 'Sami Ullah']);

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK]);

        $this->actingAs($this->admin())
            ->getJson('/api/admin/videos')
            ->assertOk()
            ->assertJsonPath('data.0.owner.kind', 'agent')
            ->assertJsonPath('data.0.owner.name', 'Sami Ullah');
    }

    public function test_the_admin_list_names_the_agency_behind_a_video(): void
    {
        $agency = $this->agency();

        $this->actingAs($agency->owner)
            ->postJson('/api/agency/videos', ['title' => 'Ours', 'youtubeUrl' => self::LINK]);

        $this->actingAs($this->admin())
            ->getJson('/api/admin/videos')
            ->assertOk()
            ->assertJsonPath('data.0.owner.kind', 'agency')
            ->assertJsonPath('data.0.owner.name', 'An Agency');
    }

    public function test_the_sites_own_video_has_no_owner(): void
    {
        $this->editorial('A site video');

        $this->actingAs($this->admin())
            ->getJson('/api/admin/videos')
            ->assertOk()
            ->assertJsonPath('data.0.owner', null);
    }

    public function test_a_signed_out_visitor_cannot_add_a_video(): void
    {
        $this->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->assertUnauthorized();
    }

    public function test_an_ordinary_account_cannot_add_a_video(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->assertForbidden();
    }
}
