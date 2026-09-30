<?php

declare(strict_types=1);

namespace App\Actions\Property;

use App\Models\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateProperty
{
    public function execute(Property $property, array $data): Property
    {
        return DB::transaction(function () use ($property, $data) {
            $fillable = [
                'title', 'headline', 'description', 'purpose', 'negotiable',
                'beds', 'baths', 'floors',
            ];

            $mapped = [
                'propertyTypeId' => 'property_type_id',
                'locationId' => 'location_id',
                'fullLocation' => 'full_location',
                'price' => 'price',
                'areaValue' => 'area_value',
                'areaUnit' => 'area_unit',
                'livingRooms' => 'living_rooms',
                'kitchens' => 'kitchens',
                'carParking' => 'car_parking',
                'furnishing' => 'furnishing',
                'propertyCondition' => 'property_condition',
                'status' => 'status',
            ];

            $updates = [];
            foreach ($fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }
            foreach ($mapped as $camel => $snake) {
                if (array_key_exists($camel, $data)) {
                    $updates[$snake] = $data[$camel];
                }
            }

            $property->update($updates);

            if (array_key_exists('features', $data)) {
                $property->features()->delete();
                if (! empty($data['features'])) {
                    $features = array_map(fn (string $name) => ['name' => $name], $data['features']);
                    $property->features()->createMany($features);
                }
            }

            if (array_key_exists('nearbyPlaces', $data)) {
                $property->nearbyPlaces()->delete();
                if (! empty($data['nearbyPlaces'])) {
                    $property->nearbyPlaces()->createMany($data['nearbyPlaces']);
                }
            }

            if (! empty($data['images'])) {
                $this->storeImages($property, $data['images']);
            }

            $property->load(['images', 'features', 'nearbyPlaces', 'propertyType', 'location']);

            return $property;
        });
    }

    /**
     * @param UploadedFile[] $images
     */
    private function storeImages(Property $property, array $images): void
    {
        $maxSort = $property->images()->max('sort_order') ?? -1;

        foreach ($images as $index => $image) {
            $path = $image->store("properties/{$property->id}", 'public');

            $property->images()->create([
                'path' => $path,
                'is_cover' => $property->images()->count() === 0 && $index === 0,
                'sort_order' => $maxSort + $index + 1,
            ]);
        }
    }
}
