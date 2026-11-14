<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category?->name ?? '',
            'categorySlug' => $this->category?->slug ?? '',
            'excerpt' => $this->excerpt,
            'content' => $this->embedVideoLinks($this->content ?? ''),
            'publishedAt' => $this->published_at?->toDateString(),
            'readTime' => $this->read_time,
            'image' => $imageService->url($this->image ?? ''),
            'featured' => $this->featured,
            'status' => $this->status->value,
            'author' => [
                'id' => (string) $this->author->id,
                'name' => $this->author->name,
                'title' => $this->author->title ?? 'Content Writer',
                'avatar' => $imageService->url($this->author->avatar ?? ''),
            ],
            'metaTitle' => $this->meta_title ?? $this->title,
            'metaDescription' => $this->meta_description ?? mb_substr(strip_tags($this->excerpt), 0, 160),
            'tags' => $this->tags->map(fn ($tag) => [
                'id' => (string) $tag->id,
                'slug' => $tag->slug,
                'name' => $tag->name,
            ])->all(),
        ];
    }

    /**
     * A bare YouTube link, on its own line, becomes the video.
     *
     * Posts written before the editor understood video carry the address
     * as an anchor, which reads as a blue line of text where a reader
     * expects to watch something. Only a link that is the whole paragraph
     * is replaced — one written into a sentence is still a link.
     *
     * Done on the way out so the post keeps what was typed.
     */
    private function embedVideoLinks(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'youtu')) {
            return $html;
        }

        $pattern = '#<p>\s*<a[^>]*href="([^"]*(?:youtu\.be/|youtube\.com/(?:watch\?[^"]*v=|embed/|shorts/|live/))[^"]*)"[^>]*>.*?</a>\s*</p>#is';

        return preg_replace_callback($pattern, function (array $match) {
            $id = Video::extractYoutubeId(html_entity_decode($match[1]));

            if ($id === null) {
                return $match[0];
            }

            return '<iframe src="https://www.youtube.com/embed/'.$id.'" width="560" height="315"'
                .' frameborder="0" allowfullscreen title="YouTube video"></iframe>';
        }, $html) ?? $html;
    }
}
