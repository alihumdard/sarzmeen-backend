<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        $rawImages = $this->relationLoaded('images')
            ? $this->images->pluck('path')->all()
            : [];

        $images = array_map(fn (string $p) => $imageService->url($p), $rawImages);

        $cover = $this->relationLoaded('images')
            ? $imageService->url(
                $this->images->firstWhere('is_cover', true)?->path ?? ($rawImages[0] ?? '')
            )
            : '';

        $conditionLabel = $this->property_condition?->value
            ? str_replace('_', ' ', ucwords($this->property_condition->value, '_'))
            : null;

        $furnishingLabel = $this->furnishing?->value
            ? ucfirst($this->furnishing->value === 'semi' ? 'Semi Furnished' : $this->furnishing->value)
            : null;

        // Descriptions are sanitised HTML from the rich-text editor. Keep the
        // plain-text split for components that still expect paragraphs.
        $plain = $this->description
            ? trim(html_entity_decode(strip_tags(preg_replace('#</?(p|br|h[1-6]|li|div|blockquote)[^>]*>#i', "\n", $this->description) ?? '')))
            : '';

        $descriptionParagraphs = $plain !== ''
            ? array_values(array_filter(array_map('trim', explode("\n", $plain))))
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
            // The detail page picks which spec fields to show from this; its
            // absence left every type-specific section blank.
            'typeSlug' => $this->propertyType->slug,
            'listedBy' => ucfirst($this->listed_by->value),
            'listedOn' => $this->published_at?->toISOString(),
            'listedAgo' => $this->published_at?->diffForHumans(),
            'description' => $this->description,
            'descriptionParagraphs' => $descriptionParagraphs,
            'descriptionHtml' => $this->description ?? '',
            'highlights' => [],
            'features' => $this->whenLoaded('features', fn () => $this->features->pluck('name')->all()),
            'nearbyPlaces' => $this->whenLoaded('nearbyPlaces', fn () => $this->nearbyPlaces->map(fn ($p) => [
                'name' => $p->name,
                'distance' => $p->distance,
                'kind' => $p->kind->value,
            ])->all()),
            'specs' => $this->specs ?: new \stdClass(),
            'customFields' => $this->custom_fields ?: [],
            'agent' => new PropertyAgentResource($this->whenLoaded('owner')),
            'metaTitle' => $this->meta_title ?? $this->title,
            'metaDescription' => $this->meta_description ?? mb_substr(strip_tags($this->description), 0, 160),
        ];
    }
}
