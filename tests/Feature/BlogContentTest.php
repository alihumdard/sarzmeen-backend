<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * What a blog post's body may carry.
 *
 * It is written as HTML and rendered as HTML on the public page, and was
 * going through untouched — whatever reached the column reached the
 * reader. It now goes through an allow-list that keeps the formatting and
 * an embedded video, and drops everything a script could travel in.
 */
class BlogContentTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        $permission = Permission::findOrCreate('blogs.manage', 'web');
        $role = Role::findOrCreate('admin', 'web');
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function payload(string $content, array $overrides = []): array
    {
        $category = BlogCategory::firstOrCreate(
            ['slug' => 'guides'],
            ['name' => 'Guides'],
        );

        return array_merge([
            'title' => 'A post',
            /* Lowercase: the permalink rule rejects anything else. */
            'slug' => 'a-post-'.strtolower(str()->random(8)),
            'excerpt' => 'A short summary of the post.',
            'content' => $content,
            'categoryId' => $category->id,
            'status' => 'draft',
        ], $overrides);
    }

    private function save(string $content): string
    {
        $response = $this->actingAs($this->editor())
            ->postJson('/api/admin/blogs', $this->payload($content));

        $response->assertCreated();

        return Blog::latest('id')->value('content');
    }

    public function test_a_post_can_embed_a_youtube_video(): void
    {
        $saved = $this->save(
            '<p>Watch this.</p>'
            .'<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315" allowfullscreen></iframe>'
        );

        $this->assertStringContainsString('<iframe', $saved);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $saved);
    }

    public function test_a_post_keeps_its_formatting(): void
    {
        $saved = $this->save(
            '<h2>A heading</h2><p>A <strong>bold</strong> word and <em>emphasis</em>.</p>'
            .'<ul><li>One</li></ul><img src="https://example.com/a.jpg" alt="a">'
            .'<a href="https://example.com">a link</a>'
        );

        foreach (['<h2>', '<strong>bold</strong>', '<em>', '<li>One</li>',
            'src="https://example.com/a.jpg"', 'href="https://example.com"'] as $fragment) {
            $this->assertStringContainsString($fragment, $saved, "lost: {$fragment}");
        }
    }

    /*
     * An iframe is the one tag here that loads another page, so the
     * allow-list has to hold its source as tightly as it holds a script.
     */

    public function test_an_iframe_cannot_point_anywhere_else(): void
    {
        $saved = $this->save('<iframe src="https://evil.test/steal"></iframe>');

        $this->assertStringNotContainsString('evil.test', $saved);
    }

    public function test_a_post_cannot_carry_a_script(): void
    {
        $saved = $this->save(
            '<p>Safe.</p><script>alert(1)</script>'
            .'<img src=x onerror="alert(2)"><a href="javascript:alert(3)">link</a>'
            .'<p onclick="alert(4)">clickable</p>'
        );

        $this->assertStringContainsString('Safe.', $saved);

        foreach (['<script', 'onerror', 'javascript:', 'onclick'] as $fragment) {
            $this->assertStringNotContainsString($fragment, $saved, "kept: {$fragment}");
        }
    }
}
