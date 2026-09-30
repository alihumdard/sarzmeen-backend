<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = $this->relationLoaded('images')
            ? $this->images->pluck('path')->all()
            : [];

        $cover = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_cover', true)?->path ?? ($images[0] ?? '')
            : '';

        $conditionLabel = $this->property_condition?->value
            ? str_replace('_', ' ', ucwords($this->property_condition->value, '_'))
            : null;

        $furnishingLabel = $this->furnishing?->value
            ? ucfirst($this->furnishing->value === 'semi' ? 'Semi Furnished' : $this->furnishing->value)
            : null;

        $descriptionParagraphs = $this->description
            ? array_values(array_filter(explode("\n", $this->description)))
            : [];

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'headline' => $this->headline ?? $this->title,
            'location' => $this->full_location,
            'fullLocation' => $this->full_location,
            'propertyId' => $this->reference,
            'project' => $this->when($this->project_id, fn () => $this->project_id),
            'purpose' => $this->purpose->value,
            'price' => (float) $this->price,
            'area' => $this->formattedArea(),
            'beds' => $this->beds,
            'baths' => $this->baths,
            'image' => $cover,
            'images' => $images,
            'photoCount' => count($images),
            'featured' => $this->featured,
            'verified' => $this->verified,
            'views' => $this->views,
            'negotiable' => $this->negotiable,
            'livingRooms' => $this->living_rooms ?? 0,
            'kitchens' => $this->kitchens ?? 0,
            'carParking' => $this->car_parking ?? 0,
            'floors' => $this->floors ?? 0,
            'propertyStatus' => $conditionLabel,
            'furnishing' => $furnishingLabel,
            'propertyType' => $this->propertyType->name,
            'listedBy' => ucfirst($this->listed_by->value),
            'listedOn' => $this->published_at?->toISOString(),
            'listedAgo' => $this->published_at?->diffForHumans(),
            'description' => $this->description,
            'descriptionParagraphs' => $descriptionParagraphs,
            'highlights' => [],
            'features' => $this->whenLoaded('features', fn () => $this->features->pluck('name')->all()),
            'nearbyPlaces' => $this->whenLoaded('nearbyPlaces', fn () => $this->nearbyPlaces->map(fn ($p) => [
                'name' => $p->name,
                'distance' => $p->distance,
                'kind' => $p->kind->value,
            ])->all()),
            'agent' => new PropertyAgentResource($this->whenLoaded('owner')),
        ];
    }
}
