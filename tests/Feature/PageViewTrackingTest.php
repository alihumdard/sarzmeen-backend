<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PageViewLog;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Counting views of everything but properties.
 *
 * Only properties were ever counted, so nobody could say whether people
 * came for the projects, read the blog, or looked at an agency at all.
 */
class PageViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $permission = Permission::findOrCreate('blogs.manage', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function project(): Project
    {
        $category = ProjectCategory::firstOrCreate(
            ['slug' => 'residential'],
            ['name' => 'Residential'],
        );
        $location = Location::firstOrCreate(['slug' => 'lahore'], ['name' => 'Lahore']);

        return Project::create([
            'name' => 'A project',
            'slug' => 'a-project-'.strtolower(str()->random(6)),
            'project_category_id' => $category->id,
            'location_id' => $location->id,
            'developer' => 'A developer',
            'status' => 'Pre-Launch',
        ]);
    }

    private function blog(string $title): \App\Models\Blog
    {
        $category = \App\Models\BlogCategory::firstOrCreate(
            ['slug' => 'guides'],
            ['name' => 'Guides'],
        );

        return \App\Models\Blog::create([
            'title' => $title,
            'slug' => str($title)->slug()->value().'-'.strtolower(str()->random(5)),
            'excerpt' => 'A summary.',
            'content' => '<p>Text.</p>',
            'blog_category_id' => $category->id,
            'author_id' => User::factory()->create()->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    private function service(): Service
    {
        return Service::create([
            'slug' => 'a-service-'.strtolower(str()->random(6)),
            'title' => 'A service',
            'outcome' => 'An outcome',
            'summary' => 'What it does.',
            'points' => ['One'],
            'icon' => 'handshake',
            'status' => 'published',
        ]);
    }

    public function test_opening_a_project_counts_a_view(): void
    {
        $project = $this->project();

        $this->getJson("/api/projects/{$project->slug}")->assertOk();

        $this->assertSame(1, PageViewLog::where('viewable_type', 'project')
            ->where('viewable_id', $project->id)->value('count'));
    }

    public function test_a_second_view_the_same_day_adds_to_the_row(): void
    {
        $project = $this->project();

        $this->getJson("/api/projects/{$project->slug}")->assertOk();
        $this->getJson("/api/projects/{$project->slug}")->assertOk();

        /* One row for the day, not two. */
        $this->assertSame(1, PageViewLog::where('viewable_type', 'project')->count());
        $this->assertSame(2, PageViewLog::where('viewable_type', 'project')->value('count'));
    }

    public function test_opening_a_service_counts_a_view(): void
    {
        $service = $this->service();

        $this->getJson("/api/services/{$service->slug}")->assertOk();

        $this->assertSame(1, PageViewLog::where('viewable_type', 'service')
            ->where('viewable_id', $service->id)->value('count'));
    }

    public function test_a_page_that_is_not_found_counts_nothing(): void
    {
        $this->getJson('/api/projects/no-such-project')->assertNotFound();

        $this->assertSame(0, PageViewLog::count());
    }

    /*
     * The dashboard card.
     */

    public function test_the_overview_reports_views_by_type(): void
    {
        $project = $this->project();
        $service = $this->service();

        $this->getJson("/api/projects/{$project->slug}");
        $this->getJson("/api/projects/{$project->slug}");
        $this->getJson("/api/services/{$service->slug}");

        $data = $this->actingAs($this->admin())
            ->getJson('/api/admin/analytics/overview')
            ->assertOk()
            ->json('data.viewsByType');

        $this->assertSame('project', $data[0]['kind']);
        $this->assertSame('Projects', $data[0]['label']);
        $this->assertSame(2, $data[0]['count']);

        $this->assertSame('service', $data[1]['kind']);
        $this->assertSame(1, $data[1]['count']);
    }

    public function test_a_kind_nobody_looked_at_is_left_out(): void
    {
        $this->getJson("/api/projects/{$this->project()->slug}");

        $kinds = collect(
            $this->actingAs($this->admin())
                ->getJson('/api/admin/analytics/overview')
                ->json('data.viewsByType')
        )->pluck('kind');

        $this->assertTrue($kinds->contains('project'));
        $this->assertFalse($kinds->contains('blog'));
    }

    /*
     * The blog sections on the dashboard.
     */

    public function test_the_most_read_posts_are_listed_in_order(): void
    {
        $quiet = $this->blog('A quiet post');
        $busy = $this->blog('A busy post');

        PageViewLog::create(['viewable_type' => 'blog', 'viewable_id' => $quiet->id, 'date' => now()->toDateString(), 'count' => 3]);
        PageViewLog::create(['viewable_type' => 'blog', 'viewable_id' => $busy->id, 'date' => now()->toDateString(), 'count' => 40]);

        $data = $this->actingAs($this->admin())
            ->getJson('/api/admin/analytics/top-blogs')
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $data);
        $this->assertSame('A busy post', $data[0]['title']);
        $this->assertSame(40, $data[0]['views']);
        $this->assertSame('A quiet post', $data[1]['title']);
    }

    public function test_a_post_deleted_since_it_was_read_is_dropped(): void
    {
        $blog = $this->blog('Gone');
        PageViewLog::create(['viewable_type' => 'blog', 'viewable_id' => $blog->id, 'date' => now()->toDateString(), 'count' => 5]);
        $blog->forceDelete();

        $this->actingAs($this->admin())
            ->getJson('/api/admin/analytics/top-blogs')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_the_blog_trend_fills_in_the_quiet_days(): void
    {
        $blog = $this->blog('A post');
        PageViewLog::create(['viewable_type' => 'blog', 'viewable_id' => $blog->id, 'date' => now()->toDateString(), 'count' => 7]);

        $response = $this->actingAs($this->admin())
            ->getJson('/api/admin/analytics/blog-views-trend?days=7')
            ->assertOk();

        /* Seven points whatever the data, so the chart draws a line. */
        $this->assertCount(7, $response->json('data'));
        $this->assertSame(7, $response->json('total'));
        $this->assertSame(0, $response->json('data.0.count'));
    }

    public function test_only_blog_views_reach_the_blog_sections(): void
    {
        $blog = $this->blog('A post');
        PageViewLog::create(['viewable_type' => 'blog', 'viewable_id' => $blog->id, 'date' => now()->toDateString(), 'count' => 4]);

        /* A project with the same id must not be counted as the post. */
        PageViewLog::create(['viewable_type' => 'project', 'viewable_id' => $blog->id, 'date' => now()->toDateString(), 'count' => 99]);

        $this->assertSame(4, $this->actingAs($this->admin())
            ->getJson('/api/admin/analytics/blog-views-trend')
            ->json('total'));
    }

    /** An unrecognised kind is ignored rather than stored. */
    public function test_an_unknown_kind_is_not_recorded(): void
    {
        PageViewLog::record('something-else', 1);

        $this->assertSame(0, PageViewLog::count());
    }
}
