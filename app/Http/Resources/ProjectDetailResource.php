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

        /*
         * The description now comes from the rich-text editor, which produces
         * HTML with no newlines - splitting on a newline left the whole thing
         * as one paragraph of visible tags. Block-ending tags become line
         * breaks first, matching how a property description is handled.
         */
        $plain = $this->description
            ? trim(html_entity_decode(strip_tags(preg_replace('#</?(p|br|h[1-6]|li|div|blockquote)[^>]*>#i', "\n", $this->description) ?? '')))
            : '';

        $descriptionParagraphs = $plain !== ''
            ? array_values(array_filter(array_map('trim', explode("\n", $plain))))
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
                // Structured fields; null `component` means this row is still
                // free text and cannot be calculated with.
                'component' => $p->component,
                'percent' => $p->percent !== null ? (float) $p->percent : null,
                'amount' => $p->amount !== null ? (float) $p->amount : null,
                'count' => (int) $p->count,
                'frequency' => $p->frequency,
                'pinned' => $p->pinned,
                'planName' => $p->plan_name,
            ])->all()),
            'towers' => $this->whenLoaded('towers', fn () => $this->towers->map(fn ($t) => [
                'id' => (string) $t->id,
                'name' => $t->name,
                'floorsCount' => $t->floors_count,
                'unitsCount' => $t->units_count,
                'image' => $t->image,
                'description' => $t->description,
                'floors' => $t->relationLoaded('floors')
                    ? $t->floors->map(fn ($f) => [
                        'id' => (string) $f->id,
                        'name' => $f->displayName(),
                        'floorNumber' => $f->floor_number,
                        'floorPlanImage' => $f->floor_plan_image,
                        'floorPlanPdf' => $f->floor_plan_pdf,
                    ])->all()
                    : [],
            ])->all()),
            // Per-project SEO; falls back to the site-wide settings when null.
            'seo' => [
                'metaTitle' => $this->meta_title,
                'metaDescription' => $this->meta_description,
                'focusKeyword' => $this->focus_keyword,
                'canonicalUrl' => $this->canonical_url,
                'ogTitle' => $this->og_title,
                'ogDescription' => $this->og_description,
                'ogImage' => $this->og_image,
            ],
            'totalArea' => $this->total_area,
            'plotSizes' => $this->whenLoaded('plotSizes', fn () => $this->plotSizes->pluck('size')->all()),
            'units' => $this->whenLoaded('units', fn () => $this->units->map(fn ($u) => [
                'id' => (string) $u->id,
                'unitType' => $u->unit_type,
                'unitName' => $u->unit_name,
                // Tower/floor placement; null when the project has no towers.
                'towerId' => $u->project_tower_id !== null ? (string) $u->project_tower_id : null,
                'floorId' => $u->project_floor_id !== null ? (string) $u->project_floor_id : null,
                'unitNumber' => $u->unit_number,
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
