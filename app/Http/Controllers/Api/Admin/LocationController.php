<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $locations = Cache::remember('taxonomy_locations', 300, function () {
            return Location::with('parent')
                ->orderBy('type')
                ->orderBy('name')
                ->get();
        });

        return LocationResource::collection($locations);
    }

    public function show(Location $location): LocationResource
    {
        $location->load('parent');

        return new LocationResource($location);
    }

    public function store(LocationRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['parent'])) {
            $data['parent_id'] = Location::where('slug', $data['parent'])->value('id');
        }
        unset($data['parent']);

        $location = Location::create($data);
        $location->load('parent');

        Cache::forget('taxonomy_locations');

        return (new LocationResource($location))
            ->response()
            ->setStatusCode(201);
    }

    public function update(LocationRequest $request, Location $location): LocationResource
    {
        $data = $request->validated();

        if (array_key_exists('parent', $data)) {
            $data['parent_id'] = $data['parent']
                ? Location::where('slug', $data['parent'])->value('id')
                : null;
        }
        unset($data['parent']);

        $location->update($data);
        $location->load('parent');

        Cache::forget('taxonomy_locations');

        return new LocationResource($location);
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();
        Cache::forget('taxonomy_locations');

        return response()->json(null, 204);
    }
}
