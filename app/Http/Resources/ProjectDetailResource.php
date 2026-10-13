<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectDetailResource extends JsonResource
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
            'projectType' => $this->project_type?->value,
            'projectTypeLabel' => $this->project_type?->label(),
            'projectFormat' => $this->project_format?->value,
            'projectFormatLabel' => $this->project_format?->label(),
            'paymentType' => $this->payment_type?->value,
            'paymentTypeLabel' => $this->payment_type?->label(),
            'towersCount' => $this->towers_count,
            'totalFloors' => $this->total_floors,
            'basementFloors' => $this->basement_floors,
            'parkingSpaces' => $this->parking_spaces,
            'expectedCompletion' => $this->expected_completion,
            'possessionTimeline' => $this->possession_timeline,
            // Raw ids so the admin edit form can preselect its dropdowns.
            'categoryId' => (string) $this->project_category_id,
            'locationId' => (string) $this->location_id,
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
            'units' => $this->whenLoaded('units', fn () => $this->units->map(fn ($u) => [
                'id' => (string) $u->id,
                'unitType' => $u->unit_type,
                'unitName' => $u->unit_name,
                'category' => $u->category->value,
                'categoryLabel' => $u->category->label(),
                'area' => (float) $u->area,
                'minArea' => $u->min_area !== null ? (float) $u->min_area : null,
                'maxArea' => $u->max_area !== null ? (float) $u->max_area : null,
                'areaUnit' => $u->area_unit,
                'ratePerSqft' => (float) $u->rate_per_sqft,
                'startingPrice' => $u->starting_price !== null ? (float) $u->starting_price : null,
                'maxPrice' => $u->max_price !== null ? (float) $u->max_price : null,
                'availability' => $u->availability->value,
                'availabilityLabel' => $u->availability->label(),
                'details' => (object) ($u->details ?: []),
                'totalPrice' => $u->total_price,
            ])->all()),
            'paymentTerms' => $this->payment_terms ?: null,
            'developerId' => $this->developer_id ? (string) $this->developer_id : null,
            'developerProfile' => $this->whenLoaded('developerProfile', fn () => $this->developerProfile ? [
                'id' => (string) $this->developerProfile->id,
                'name' => $this->developerProfile->name,
                'slug' => $this->developerProfile->slug,
                'logo' => $imageService->url($this->developerProfile->logo ?? ''),
                'description' => $this->developerProfile->description,
                'phone' => $this->developerProfile->phone,
                'email' => $this->developerProfile->email,
                'website' => $this->developerProfile->website,
                'address' => $this->developerProfile->address,
                'verified' => $this->developerProfile->verified,
            ] : null),
            'faqs' => $this->whenLoaded('faqs', fn () => $this->faqs->map(fn ($f) => [
                'id' => (string) $f->id,
                'question' => $f->question,
                'answer' => $f->answer,
            ])->all()),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'id' => (string) $d->id,
                'title' => $d->title,
                'type' => $d->type,
                'url' => $imageService->url($d->path),
                'isPublic' => $d->is_public,
            ])->all()),
            'floorPlans' => $this->whenLoaded('floorPlans', fn () => $this->floorPlans->map(fn ($p) => [
                'id' => (string) $p->id,
                'name' => $p->name,
                'floorNumber' => $p->floor_number,
                'tower' => $p->tower,
                'image' => $p->image ? $imageService->url($p->image) : null,
                'pdf' => $p->pdf ? $imageService->url($p->pdf) : null,
                'description' => $p->description,
            ])->all()),
            'nearbyPlaces' => [],
            'metaTitle' => $this->meta_title ?? $this->name,
            'metaDescription' => $this->meta_description ?? mb_substr(strip_tags($this->description), 0, 160),
        ];
    }
}
