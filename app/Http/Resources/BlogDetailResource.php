<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
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
            'content' => $this->content,
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
}
