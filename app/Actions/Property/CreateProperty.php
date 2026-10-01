<?php

declare(strict_types=1);

namespace App\Actions\Property;

use App\Enums\ListedBy;
use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProperty
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}
    public function execute(User $user, array $data): Property
    {
        return DB::transaction(function () use ($user, $data) {
            $slug = Str::slug($data['title']) . '-' . Str::random(6);

            $property = Property::create([
                'slug' => $slug,
                'reference' => 'SZ-' . strtoupper(Str::random(6)),
                'title' => $data['title'],
                'headline' => $data['headline'] ?? null,
                'description' => $data['description'] ?? '',
                'owner_id' => $user->id,
                'owner_type' => User::class,
                'agency_id' => $user->hasRole('agency')
                    ? $user->agency?->id
                    : ($user->agent?->agency_id ?? null),
                'property_type_id' => $data['propertyTypeId'],
                'location_id' => $data['locationId'],
                'full_location' => $data['fullLocation'] ?? '',
                'purpose' => $data['purpose'],
                'price' => $data['price'],
                'negotiable' => $data['negotiable'] ?? false,
                'area_value' => $data['areaValue'],
                'area_unit' => $data['areaUnit'],
                'beds' => $data['beds'] ?? null,
                'baths' => $data['baths'] ?? null,
                'living_rooms' => $data['livingRooms'] ?? null,
                'kitchens' => $data['kitchens'] ?? null,
                'car_parking' => $data['carParking'] ?? null,
                'floors' => $data['floors'] ?? null,
                'furnishing' => $data['furnishing'] ?? null,
                'property_condition' => $data['propertyCondition'] ?? null,
                'listed_by' => $this->resolveListedBy($user),
                'status' => PropertyStatus::Draft,
                'meta_title' => $data['metaTitle'] ?? null,
                'meta_description' => $data['metaDescription'] ?? null,
            ]);

            if (! empty($data['features'])) {
                $features = array_map(fn (string $name) => ['name' => $name], $data['features']);
                $property->features()->createMany($features);
            }

            if (! empty($data['nearbyPlaces'])) {
                $property->nearbyPlaces()->createMany($data['nearbyPlaces']);
            }

            if (! empty($data['images'])) {
                $this->storeImages($property, $data['images']);
            }

            $property->load(['images', 'features', 'nearbyPlaces', 'propertyType', 'location']);

            return $property;
        });
    }

    private function resolveListedBy(User $user): ListedBy
    {
        if ($user->hasRole('agency')) {
            return ListedBy::Agency;
        }

        if ($user->hasRole('agent')) {
            return ListedBy::Agent;
        }

        return ListedBy::Owner;
    }

    /**
     * @param UploadedFile[] $images
     */
    private function storeImages(Property $property, array $images): void
    {
        foreach ($images as $index => $image) {
            $path = $this->imageService->upload($image, "properties/{$property->id}");

            $property->images()->create([
                'path' => $path,
                'is_cover' => $index === 0,
                'sort_order' => $index,
            ]);
        }
    }
}
