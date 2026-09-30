<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name, ''),
            'categorySlug' => $this->whenLoaded('category', fn () => $this->category?->slug, ''),
            'excerpt' => $this->excerpt,
            'publishedAt' => $this->published_at?->toDateString(),
            'readTime' => $this->read_time,
            'image' => $this->image ?? '',
            'featured' => $this->featured,
            'status' => $this->status->value,
            'author' => $this->whenLoaded('author', fn () => [
                'id' => (string) $this->author->id,
                'name' => $this->author->name,
                'title' => $this->author->title ?? 'Content Writer',
                'avatar' => $this->author->avatar ?? '',
            ]),
        ];
    }
}
