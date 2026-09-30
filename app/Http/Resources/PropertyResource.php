<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_cover', true)?->path ?? $this->images->first()?->path
            : null;

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'location' => $this->full_location,
            'project' => $this->when($this->project_id, fn () => $this->project_id),
            'purpose' => $this->purpose->value,
            'price' => (float) $this->price,
            'area' => $this->formattedArea(),
            'beds' => $this->beds,
            'baths' => $this->baths,
            'image' => $cover ?? '',
            'featured' => $this->featured,
            'agent' => new PropertyAgentResource($this->whenLoaded('owner')),
            'description' => $this->when($this->description, $this->description),
            'features' => $this->whenLoaded('features', fn () => $this->features->pluck('name')->all()),
            'listedAgo' => $this->published_at?->diffForHumans(),
            'verified' => $this->when($this->verified, true),
        ];
    }
}
