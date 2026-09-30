<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_cover', true)?->path ?? $this->images->first()?->path
            : null;

        $cityName = '';
        if ($this->relationLoaded('location')) {
            $loc = $this->location;
            while ($loc && $loc->type->value !== 'city') {
                $loc = $loc->parent;
            }
            $cityName = $loc?->name ?? $this->location->name;
        }

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'city' => $cityName,
            'category' => $this->whenLoaded('category', fn () => $this->category->name, ''),
            'image' => $cover ?? '',
            'status' => $this->status,
            'developer' => $this->developer,
            'priceFrom' => $this->price_from,
            'verified' => $this->when($this->verified, true),
            'featured' => $this->when($this->featured, true),
        ];
    }
}
