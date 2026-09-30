<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = $this->relationLoaded('images')
            ? $this->images->pluck('path')->all()
            : [];

        $cover = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_cover', true)?->path ?? ($images[0] ?? '')
            : '';

        $cityName = '';
        if ($this->relationLoaded('location')) {
            $loc = $this->location;
            while ($loc && $loc->type->value !== 'city') {
                $loc = $loc->parent;
            }
            $cityName = $loc?->name ?? $this->location->name;
        }

        $descriptionParagraphs = $this->description
            ? array_values(array_filter(explode("\n", $this->description)))
            : [];

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'city' => $cityName,
            'category' => $this->whenLoaded('category', fn () => $this->category->name, ''),
            'image' => $cover,
            'images' => $images,
            'status' => $this->status,
            'developer' => $this->developer,
            'priceFrom' => $this->price_from,
            'verified' => $this->verified,
            'featured' => $this->featured,
            'fullLocation' => $this->full_location,
            'description' => $this->description,
            'descriptionParagraphs' => $descriptionParagraphs,
            'highlights' => $this->whenLoaded('highlights', fn () => $this->highlights->pluck('text')->all()),
            'amenities' => $this->whenLoaded('amenities', fn () => $this->amenities->pluck('name')->all()),
            'paymentPlan' => $this->whenLoaded('paymentPlans', fn () => $this->paymentPlans->map(fn ($p) => [
                'label' => $p->label,
                'value' => $p->value,
            ])->all()),
            'totalArea' => $this->total_area,
            'plotSizes' => $this->whenLoaded('plotSizes', fn () => $this->plotSizes->pluck('size')->all()),
            'nearbyPlaces' => [],
        ];
    }
}
