<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

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
            'image' => $imageService->url($cover ?? ''),
            'status' => $this->status,
            'developer' => $this->developer,
            // Card shows Type + Format together (spec section 3).
            'projectType' => $this->project_type?->value,
            'projectTypeLabel' => $this->project_type?->label(),
            'projectFormat' => $this->project_format?->value,
            'projectFormatLabel' => $this->project_format?->label(),
            'paymentTypeLabel' => $this->payment_type?->label(),
            'priceFrom' => $this->price_from,
            'verified' => $this->when($this->verified, true),
            'featured' => $this->when($this->featured, true),
        ];
    }
}
