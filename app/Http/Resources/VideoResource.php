<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        // An uploaded still wins; otherwise YouTube's own is used, so a video
        // always has a card image without the admin having to make one.
        $thumbnail = $this->thumbnail
            ? $imageService->url($this->thumbnail)
            : "https://img.youtube.com/vi/{$this->youtube_id}/hqdefault.jpg";

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'youtubeId' => $this->youtube_id,
            'youtubeUrl' => $this->youtube_url,
            'embedUrl' => "https://www.youtube.com/embed/{$this->youtube_id}",
            'thumbnail' => $thumbnail,
            'status' => $this->status->value,
            'featured' => $this->featured,
            'views' => $this->views,
            'sortOrder' => $this->sort_order,
            'publishedAt' => $this->published_at?->toDateString(),
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
            'createdAt' => $this->created_at->toISOString(),
        ];
    }
}
