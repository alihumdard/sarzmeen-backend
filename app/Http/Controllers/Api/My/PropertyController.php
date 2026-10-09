<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Actions\Property\CreateProperty;
use App\Actions\Property\UpdateProperty;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PropertyController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = $this->scopedQuery($user)
            ->with(['images', 'features', 'propertyType', 'location'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return PropertyResource::collection($query->paginate(12));
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        Gate::authorize('view', $property);

        $property->load(['images', 'features', 'propertyType', 'propertyType.category', 'location']);

        return response()->json([
            'data' => [
                'id' => $property->id,
                'title' => $property->title,
                'headline' => $property->headline,
                'description' => $property->description,
                'purpose' => $property->purpose->value,
                'price' => (float) $property->price,
                'negotiable' => $property->negotiable,
                'areaValue' => (float) $property->area_value,
                'areaUnit' => $property->area_unit->value,
                'beds' => $property->beds,
                'baths' => $property->baths,
                'livingRooms' => $property->living_rooms,
                'kitchens' => $property->kitchens,
                'carParking' => $property->car_parking,
                'floors' => $property->floors,
                'furnishing' => $property->furnishing?->value,
                'propertyCondition' => $property->property_condition?->value,
                'status' => $property->status->value,
                'categoryId' => $property->propertyType?->category_id,
                'propertyTypeId' => $property->property_type_id,
                'propertyType' => $property->propertyType?->name,
                'typeSlug' => $property->propertyType?->slug,
                'category' => $property->propertyType?->category?->name,
                'locationId' => $property->location_id,
                'location' => $property->full_location,
                'fullLocation' => $property->full_location,
                'specs' => $property->specs ?: new \stdClass(),
                'features' => $property->features->pluck('name')->all(),
                'images' => $property->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $this->imageService->url($img->path),
                    'isCover' => (bool) $img->is_cover,
                ])->all(),
                'metaTitle' => $property->meta_title,
                'metaDescription' => $property->meta_description,
            ],
        ]);
    }

    public function store(StorePropertyRequest $request, CreateProperty $action): JsonResponse
    {
        $property = $action->execute(
            $request->user(),
            ['specs' => $request->specs()] + $request->validated(),
        );

        return (new PropertyResource($property))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePropertyRequest $request, Property $property, UpdateProperty $action): PropertyResource
    {
        $property = $action->execute($property, $request->validated());

        return new PropertyResource($property);
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        Gate::authorize('delete', $property);

        foreach ($property->images as $image) {
            $this->imageService->delete($image->path);
        }

        $property->delete();

        return response()->json(null, 204);
    }

    private function scopedQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        if ($user->hasRole('agency')) {
            return Property::forAgency($user->agency->id);
        }

        return Property::ownedBy($user);
    }
}
