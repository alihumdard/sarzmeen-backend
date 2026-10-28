<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        $cover = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_cover', true)?->path ?? $this->images->first()?->path
            : null;

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            // The serial the admin and the lister quote to each other.
            'reference' => $this->reference,
            'title' => $this->title,
            'location' => $this->full_location,
            'project' => $this->when($this->project_id, fn () => $this->project_id),
            'purpose' => $this->purpose->value,
            'price' => (float) $this->price,
            'area' => $this->formattedArea(),
            'beds' => $this->beds,
            'baths' => $this->baths,
            'image' => $imageService->url($cover ?? ''),
            'status' => $this->status->value,
            'views' => $this->views,
            'type' => $this->whenLoaded('propertyType', fn () => $this->propertyType->name, ''),
            'typeSlug' => $this->whenLoaded('propertyType', fn () => $this->propertyType->slug, ''),
            'specs' => $this->specs ?: new \stdClass(),
            'customFields' => $this->custom_fields ?: [],
            'featured' => $this->featured,
            'agent' => new PropertyAgentResource($this->whenLoaded('owner')),
            'agency' => $this->whenLoaded('agency', fn () => [
                'id' => (string) $this->agency->id,
                'name' => $this->agency->name,
                'slug' => $this->agency->slug,
            ]),
            /*
             * Listing cards show this as plain text, so tags have to go here —
             * otherwise an editor-written description renders as a literal
             * "<p>Well-maintained..." on the card.
             */
            'description' => $this->when(
                $this->description,
                fn () => trim(html_entity_decode(strip_tags($this->description))),
            ),
            'features' => $this->whenLoaded('features', fn () => $this->features->pluck('name')->all()),
            'listedAgo' => $this->published_at?->diffForHumans(),
            'verified' => $this->when($this->verified, true),
        ];
    }
}
