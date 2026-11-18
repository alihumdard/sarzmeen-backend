<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VideoStatus;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Review and categories on an owner's videos.
 *
 * A video used to go live the moment it was added. It waits for an
 * administrator now, and an owner groups their videos under categories
 * of their own making.
 */
class OwnerVideoReviewTest extends TestCase
{
    use RefreshDatabase;

    private const LINK = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

    private function admin(): User
    {
        $permission = Permission::findOrCreate('blogs.manage', 'web');
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
            'slug' => 'an-agent-'.strtolower(str()->random(6)),
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
            'slug' => 'an-agency-'.strtolower(str()->random(6)),
            'status' => 'approved',
        ]);
    }

    private function addVideo(Agent $agent, array $extra = []): string
    {
        return $this->actingAs($agent->user)
            ->postJson('/api/agent/videos', array_merge([
                'title' => 'A walkthrough',
                'youtubeUrl' => self::LINK,
            ], $extra))
            ->assertCreated()
            ->json('data.id');
    }

    /*
     * Review.
     */

    public function test_a_new_video_waits_for_review(): void
    {
        $agent = $this->agent();
        $id = $this->addVideo($agent);

        $this->assertSame(VideoStatus::Pending, Video::find($id)->status);

        /* And is nowhere a visitor can see it. */
        $this->getJson("/api/agents/{$agent->slug}")
            ->assertOk()
            ->assertJsonCount(0, 'data.videos');
    }

    public function test_an_approved_video_reaches_the_page(): void
    {
        $agent = $this->agent();
        $id = $this->addVideo($agent);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/videos/{$id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->getJson("/api/agents/{$agent->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data.videos');

        $video = Video::find($id);
        $this->assertNotNull($video->published_at);
        $this->assertNotNull($video->reviewed_at);
    }

    public function test_a_rejected_video_carries_its_reason(): void
    {
        $agent = $this->agent();
        $id = $this->addVideo($agent);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/videos/{$id}/reject", ['note' => 'The sound is unusable.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reviewNote', 'The sound is unusable.');

        /* The owner can read why, on their own list. */
        $this->actingAs($agent->user)
            ->getJson('/api/agent/videos')
            ->assertOk()
            ->assertJsonPath('data.0.reviewNote', 'The sound is unusable.');
    }

    public function test_rejecting_without_a_reason_is_refused(): void
    {
        $agent = $this->agent();
        $id = $this->addVideo($agent);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/videos/{$id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('note');
    }

    public function test_a_video_already_published_cannot_be_approved_again(): void
    {
        $agent = $this->agent();
        $id = $this->addVideo($agent);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson("/api/admin/videos/{$id}/approve")->assertOk();

        /* Approving again would reset the date it went up. */
        $this->actingAs($admin)
            ->postJson("/api/admin/videos/{$id}/approve")
            ->assertStatus(422);
    }

    /*
     * Editing, and what it costs.
     */

    public function test_an_owner_edits_their_own_video(): void
    {
        $agent = $this->agent();
        $id = $this->addVideo($agent);

        $this->actingAs($agent->user)
            ->putJson("/api/agent/videos/{$id}", [
                'title' => 'A better title',
                'description' => 'Now with a description.',
                'youtubeUrl' => self::LINK,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'A better title');
    }

    public function test_renaming_a_published_video_leaves_it_published(): void
    {
        $agent = $this->agent();
        $id = $this->addVideo($agent);
        $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve");

        $this->actingAs($agent->user)
            ->putJson("/api/agent/videos/{$id}", [
                'title' => 'A clearer title',
                'youtubeUrl' => self::LINK,
            ])
            ->assertOk();

        /* A new name is not a new video. */
        $this->assertSame(VideoStatus::Published, Video::find($id)->status);
    }

    public function test_changing_the_video_itself_sends_it_back_for_review(): void
    {
        $agent = $this->agent();
        $id = $this->addVideo($agent);
        $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve");

        $this->actingAs($agent->user)
            ->putJson("/api/agent/videos/{$id}", [
                'title' => 'A walkthrough',
                'youtubeUrl' => 'https://youtu.be/aaaaaaaaaaa',
            ])
            ->assertOk();

        /* What was approved is not what would now be shown. */
        $this->assertSame(VideoStatus::Pending, Video::find($id)->status);
    }

    public function test_editing_a_rejected_video_puts_it_back_in_the_queue(): void
    {
        $agent = $this->agent();
        $id = $this->addVideo($agent);
        $this->actingAs($this->admin())
            ->postJson("/api/admin/videos/{$id}/reject", ['note' => 'Too long.']);

        $this->actingAs($agent->user)
            ->putJson("/api/agent/videos/{$id}", [
                'title' => 'A shorter cut',
                'youtubeUrl' => self::LINK,
            ])
            ->assertOk();

        $video = Video::find($id);
        $this->assertSame(VideoStatus::Pending, $video->status);
        /* The old note no longer describes it. */
        $this->assertNull($video->review_note);
    }

    /*
     * Categories, which belong to their owner.
     */

    public function test_an_owner_makes_their_own_category(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/video-categories', ['name' => 'Walkthroughs'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Walkthroughs');

        $this->assertSame(1, VideoCategory::where('agent_id', $agent->id)->count());
    }

    public function test_an_owner_sees_only_their_own_categories(): void
    {
        $mine = $this->agent();
        $theirs = $this->agent();

        $this->actingAs($mine->user)->postJson('/api/agent/video-categories', ['name' => 'Mine']);
        $this->actingAs($theirs->user)->postJson('/api/agent/video-categories', ['name' => 'Theirs']);

        $this->actingAs($mine->user)
            ->getJson('/api/agent/video-categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mine');
    }

    /** Two owners may both have a "Walkthroughs" without a clash. */
    public function test_two_owners_may_use_the_same_name(): void
    {
        $one = $this->agent();
        $two = $this->agency();

        $this->actingAs($one->user)
            ->postJson('/api/agent/video-categories', ['name' => 'Walkthroughs'])
            ->assertCreated();

        $this->actingAs($two->owner)
            ->postJson('/api/agency/video-categories', ['name' => 'Walkthroughs'])
            ->assertCreated();
    }

    public function test_the_same_name_twice_for_one_owner_is_refused(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)->postJson('/api/agent/video-categories', ['name' => 'Tours']);

        $this->actingAs($agent->user)
            ->postJson('/api/agent/video-categories', ['name' => 'tours'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_a_video_can_be_filed_under_a_category(): void
    {
        $agent = $this->agent();

        $categoryId = $this->actingAs($agent->user)
            ->postJson('/api/agent/video-categories', ['name' => 'Tours'])
            ->json('data.id');

        $id = $this->addVideo($agent, ['categoryId' => $categoryId]);

        $this->assertSame((int) $categoryId, Video::find($id)->video_category_id);
    }

    /** Someone else's category id must not file a video under it. */
    public function test_another_owners_category_is_ignored(): void
    {
        $mine = $this->agent();
        $theirs = $this->agent();

        $theirCategory = $this->actingAs($theirs->user)
            ->postJson('/api/agent/video-categories', ['name' => 'Theirs'])
            ->json('data.id');

        $id = $this->addVideo($mine, ['categoryId' => $theirCategory]);

        $this->assertNull(Video::find($id)->video_category_id);
    }

    public function test_removing_a_category_leaves_its_videos(): void
    {
        $agent = $this->agent();

        $categoryId = $this->actingAs($agent->user)
            ->postJson('/api/agent/video-categories', ['name' => 'Tours'])
            ->json('data.id');

        $id = $this->addVideo($agent, ['categoryId' => $categoryId]);

        $this->actingAs($agent->user)
            ->deleteJson("/api/agent/video-categories/{$categoryId}")
            ->assertNoContent();

        /* The grouping goes; the work stays. */
        $video = Video::find($id);
        $this->assertNotNull($video);
        $this->assertNull($video->video_category_id);
    }

    /*
     * The public library: everyone's categories, grouped by owner.
     */

    public function test_the_library_groups_categories_by_owner(): void
    {
        $agency = $this->agency();

        /* The site's own grouping, with a guide in it. */
        $own = VideoCategory::create([
            'name' => 'Guides',
            'slug' => 'guides',
            'sort_order' => 0,
        ]);
        Video::create([
            'title' => 'A guide',
            'slug' => 'a-guide',
            'youtube_url' => self::LINK,
            'youtube_id' => 'dQw4w9WgXcQ',
            'video_category_id' => $own->id,
            'status' => VideoStatus::Published,
            'published_at' => now(),
        ]);

        $theirs = $this->actingAs($agency->owner)
            ->postJson('/api/agency/video-categories', ['name' => 'Walkthroughs'])
            ->json('data.id');

        $id = $this->actingAs($agency->owner)
            ->postJson('/api/agency/videos', [
                'title' => 'Ours',
                'youtubeUrl' => self::LINK,
                'categoryId' => $theirs,
            ])
            ->json('data.id');
        $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve");

        $groups = $this->getJson('/api/videos/categories')->assertOk()->json('data');

        /* The site's own first, so a visitor is not made to read a dozen
           agency groupings before reaching the guides. */
        $this->assertSame('sarzameen', $groups[0]['key']);
        $this->assertSame('Sarzameen', $groups[0]['label']);
        $this->assertSame('Guides', $groups[0]['categories'][0]['name']);

        $this->assertSame('An Agency', $groups[1]['label']);
        $this->assertSame('Walkthroughs', $groups[1]['categories'][0]['name']);
    }

    public function test_a_category_with_nothing_published_is_left_out(): void
    {
        $agency = $this->agency();

        $categoryId = $this->actingAs($agency->owner)
            ->postJson('/api/agency/video-categories', ['name' => 'Empty'])
            ->json('data.id');

        /* Added but never approved, so the category leads nowhere. */
        $this->actingAs($agency->owner)->postJson('/api/agency/videos', [
            'title' => 'Waiting',
            'youtubeUrl' => self::LINK,
            'categoryId' => $categoryId,
        ]);

        $this->getJson('/api/videos/categories')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_the_library_filters_by_category(): void
    {
        $agency = $this->agency();

        $categoryId = $this->actingAs($agency->owner)
            ->postJson('/api/agency/video-categories', ['name' => 'Walkthroughs'])
            ->json('data.id');

        foreach ([['In it', $categoryId], ['Not in it', null]] as [$title, $category]) {
            $id = $this->actingAs($agency->owner)
                ->postJson('/api/agency/videos', array_filter([
                    'title' => $title,
                    'youtubeUrl' => self::LINK,
                    'categoryId' => $category,
                ]))
                ->json('data.id');
            $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve");
        }

        $this->getJson('/api/videos?category=walkthroughs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'In it');
    }

    /** Tags are shown nowhere, so search is all they are for. */
    public function test_the_library_searches_tags(): void
    {
        $agency = $this->agency();

        $id = $this->actingAs($agency->owner)
            ->postJson('/api/agency/videos', [
                'title' => 'A walkthrough',
                'youtubeUrl' => self::LINK,
                'tags' => ['Gulberg'],
            ])
            ->json('data.id');
        $this->actingAs($this->admin())->postJson("/api/admin/videos/{$id}/approve");

        /* The word is in neither title nor description. */
        $this->getJson('/api/videos?search=Gulberg')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'A walkthrough');
    }

    public function test_an_owner_cannot_touch_another_owners_category(): void
    {
        $mine = $this->agent();
        $theirs = $this->agent();

        $theirCategory = $this->actingAs($theirs->user)
            ->postJson('/api/agent/video-categories', ['name' => 'Theirs'])
            ->json('data.id');

        $this->actingAs($mine->user)
            ->deleteJson("/api/agent/video-categories/{$theirCategory}")
            ->assertNotFound();

        $this->assertNotNull(VideoCategory::find($theirCategory));
    }
}
