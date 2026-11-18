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
     * The library, which an owner's video reaches only once approved.
     */

    public function test_an_unapproved_video_stays_out_of_the_library(): void
    {
        $agent = $this->agent();
        $this->editorial('A site video');

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->assertCreated();

        /* Waiting for review, so only the site's own is listed. */
        $this->getJson('/api/videos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'A site video');
    }

    public function test_an_approved_video_joins_the_library(): void
    {
        $agent = $this->agent();
        $this->editorial('A site video');

        $id = $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->json('data.id');

        $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve")->assertOk();

        $titles = collect($this->getJson('/api/videos')->json('data'))->pluck('title');

        $this->assertTrue($titles->contains('Mine'));
        $this->assertTrue($titles->contains('A site video'));
    }

    /** The site's own come first, whoever posted most recently. */
    public function test_the_sites_own_videos_are_listed_first(): void
    {
        $agent = $this->agent();

        $id = $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->json('data.id');
        $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve");

        /* Added after the owner's, and still expected at the top. */
        $this->editorial('A site video');

        $this->getJson('/api/videos')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'A site video');
    }

    public function test_an_approved_video_carries_its_owner(): void
    {
        $agency = $this->agency();

        $id = $this->actingAs($agency->owner)
            ->postJson('/api/agency/videos', ['title' => 'Ours', 'youtubeUrl' => self::LINK])
            ->json('data.id');
        $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve");

        $video = collect($this->getJson('/api/videos')->json('data'))
            ->firstWhere('title', 'Ours');

        /* So a viewer can reach the rest of that agency's videos. */
        $this->assertSame('agency', $video['owner']['kind']);
        $this->assertSame('An Agency', $video['owner']['name']);
        $this->assertSame('/agencies/'.$agency->slug, $video['owner']['href']);
    }

    public function test_a_site_video_has_no_owner(): void
    {
        $this->editorial('A site video');

        $video = $this->getJson('/api/videos')->json('data.0');

        $this->assertNull($video['owner']);
    }

    public function test_the_agents_page_shows_their_videos(): void
    {
        $agent = $this->agent();
        $this->editorial('A site video');

        $id = $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->assertCreated()
            ->json('data.id');

        /* It waits for an administrator before anyone sees it. */
        $this->getJson("/api/agents/{$agent->slug}")
            ->assertOk()
            ->assertJsonCount(0, 'data.videos');

        $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve")->assertOk();

        /* Theirs, and only theirs: the editorial one is not swept in. */
        $this->getJson("/api/agents/{$agent->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data.videos')
            ->assertJsonPath('data.videos.0.title', 'Mine');
    }

    public function test_the_agencys_page_shows_its_videos(): void
    {
        $agency = $this->agency();

        $id = $this->actingAs($agency->owner)
            ->postJson('/api/agency/videos', ['title' => 'Ours', 'youtubeUrl' => self::LINK])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve")->assertOk();

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

    /*
     * The link and the tags.
     */

    public function test_a_video_keeps_its_link_and_tags(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', [
                'title' => 'A walkthrough',
                'youtubeUrl' => self::LINK,
                'link' => 'https://example.com/properties/a-house',
                'tags' => ['DHA', '10 Marla'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.link', 'https://example.com/properties/a-house')
            ->assertJsonPath('data.tags', ['DHA', '10 Marla']);
    }

    public function test_a_video_needs_neither_a_link_nor_tags(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Mine', 'youtubeUrl' => self::LINK])
            ->assertCreated()
            ->assertJsonPath('data.link', null)
            ->assertJsonPath('data.tags', []);
    }

    public function test_blank_and_repeated_tags_are_dropped(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', [
                'title' => 'Mine',
                'youtubeUrl' => self::LINK,
                'tags' => ['  DHA  ', 'dha', '10 Marla'],
            ])
            ->assertCreated()
            /* Trimmed, and the same word twice is one tag. */
            ->assertJsonPath('data.tags', ['DHA', '10 Marla']);
    }

    /**
     * The link is rendered as an anchor on a public page, so an address
     * that runs script is refused where it arrives.
     */
    public function test_a_link_that_is_not_a_web_address_is_refused(): void
    {
        $agent = $this->agent();

        foreach (['javascript:alert(1)', 'data:text/html,<script>', 'not a url'] as $bad) {
            $this->actingAs($agent->user)
                ->postJson('/api/agent/videos', [
                    'title' => 'Mine',
                    'youtubeUrl' => self::LINK,
                    'link' => $bad,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors('link');
        }
    }

    public function test_more_than_ten_tags_are_refused(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', [
                'title' => 'Mine',
                'youtubeUrl' => self::LINK,
                'tags' => array_map(fn (int $i) => "tag{$i}", range(1, 11)),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tags');
    }

    /** Tags are written for finding a video by, not for showing. */
    public function test_tags_find_a_video_in_the_admin_list(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', [
                'title' => 'A walkthrough',
                'youtubeUrl' => self::LINK,
                'tags' => ['Gulberg', '10 Marla'],
            ])
            ->assertCreated();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', ['title' => 'Another', 'youtubeUrl' => self::LINK])
            ->assertCreated();

        /* The word is in neither title nor description — only a tag. */
        $this->actingAs($this->admin())
            ->getJson('/api/admin/videos?search=Gulberg')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'A walkthrough');
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
