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

    public function store(StorePropertyRequest $request, CreateProperty $action): JsonResponse
    {
        $property = $action->execute($request->user(), $request->validated());

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
        $this->authorize('delete', $property);

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
