<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyTypeRequest;
use App\Http\Resources\PropertyTypeResource;
use App\Models\Category;
use App\Models\PropertyType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class PropertyTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $types = Cache::remember('taxonomy_property_types', 300, function () {
            return PropertyType::with('category')
                ->orderByDesc('featured')
                ->orderBy('name')
                ->get();
        });

        return PropertyTypeResource::collection($types);
    }

    public function show(PropertyType $propertyType): PropertyTypeResource
    {
        $propertyType->load('category');

        return new PropertyTypeResource($propertyType);
    }

    public function store(PropertyTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['category_id'] = Category::where('slug', $data['category'])->value('id');
        unset($data['category']);

        $type = PropertyType::create($data);
        $type->load('category');

        Cache::forget('taxonomy_property_types');

        return (new PropertyTypeResource($type))
            ->response()
            ->setStatusCode(201);
    }

    public function update(PropertyTypeRequest $request, PropertyType $propertyType): PropertyTypeResource
    {
        $data = $request->validated();
        $data['category_id'] = Category::where('slug', $data['category'])->value('id');
        unset($data['category']);

        $propertyType->update($data);
        $propertyType->load('category');

        Cache::forget('taxonomy_property_types');

        return new PropertyTypeResource($propertyType);
    }

    public function destroy(PropertyType $propertyType): JsonResponse
    {
        $propertyType->delete();
        Cache::forget('taxonomy_property_types');

        return response()->json(null, 204);
    }
}
