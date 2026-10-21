<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PropertyImageController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function store(Request $request, Property $property): JsonResponse
    {
        Gate::authorize('update', $property);

        $request->validate([
            'images' => 'required|array|max:20',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:min_width=400,min_height=400',
        ]);

        $maxSort = $property->images()->max('sort_order') ?? -1;
        $isFirst = $property->images()->count() === 0;
        $uploaded = [];

        foreach ($request->file('images') as $index => $file) {
            $path = $this->imageService->upload($file, "properties/{$property->id}");

            $image = $property->images()->create([
                'path' => $path,
                'is_cover' => $isFirst && $index === 0,
                'sort_order' => $maxSort + $index + 1,
            ]);

            $uploaded[] = [
                'id' => $image->id,
                'url' => $this->imageService->url($path),
                'isCover' => $image->is_cover,
                'sortOrder' => $image->sort_order,
            ];
        }

        return response()->json(['data' => $uploaded], 201);
    }

    public function setCover(Property $property, PropertyImage $image): JsonResponse
    {
        Gate::authorize('update', $property);

        if ($image->property_id !== $property->id) {
            abort(404);
        }

        $property->images()->update(['is_cover' => false]);
        $image->update(['is_cover' => true]);

        return response()->json(['message' => 'Cover image updated']);
    }

    public function reorder(Request $request, Property $property): JsonResponse
    {
        Gate::authorize('update', $property);

        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:property_images,id',
        ]);

        foreach ($request->input('order') as $index => $imageId) {
            PropertyImage::where('id', $imageId)
                ->where('property_id', $property->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Images reordered']);
    }

    public function destroy(Property $property, PropertyImage $image): JsonResponse
    {
        Gate::authorize('update', $property);

        if ($image->property_id !== $property->id) {
            abort(404);
        }

        $this->imageService->delete($image->path);

        $wasCover = $image->is_cover;
        $image->delete();

        if ($wasCover) {
            $next = $property->images()->orderBy('sort_order')->first();
            $next?->update(['is_cover' => true]);
        }

        return response()->json(null, 204);
    }
}
