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
            /* Where the video leads, and what it is about. Both belong to
               an owner's video; the site's own guides leave them empty. */
            'link' => $this->link,
            'tags' => $this->tags ?? [],
            'embedUrl' => "https://www.youtube.com/embed/{$this->youtube_id}",
            'thumbnail' => $thumbnail,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            /* Why it was turned down, for its owner to read and act on. */
            'reviewNote' => $this->review_note,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => (string) $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'categoryId' => $this->video_category_id ? (string) $this->video_category_id : null,
            'featured' => $this->featured,
            'views' => $this->views,
            'sortOrder' => $this->sort_order,
            /*
             * Who put it here. A guide the site wrote and a walkthrough an
             * agency posted sat side by side in the admin list looking
             * identical, so an administrator could not tell which of them
             * was theirs to edit.
             */
            'owner' => $this->owner(),
            'publishedAt' => $this->published_at?->toDateString(),
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
            'createdAt' => $this->created_at->toISOString(),
        ];
    }

    /**
     * The agent or agency behind a video, or null for the site's own.
     *
     * The admin list eager-loads both, so this does not query per row.
     */
    private function owner(): ?array
    {
        if ($this->agent_id) {
            $agent = $this->agent;

            return [
                'kind' => 'agent',
                'name' => $agent?->user?->name ?? 'Unknown agent',
                'slug' => $agent?->slug,
            ];
        }

        if ($this->agency_id) {
            $agency = $this->agency;

            return [
                'kind' => 'agency',
                'name' => $agency?->name ?? 'Unknown agency',
                'slug' => $agency?->slug,
            ];
        }

        return null;
    }
}
