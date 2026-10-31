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
    public function index(): JsonResponse
    {
        /*
         * Cached already transformed, not as models. The table runs to
         * thousands of rows and only the parent's slug is ever needed, so
         * eager-loading a parent model each time and storing the whole
         * collection meant every request paid to rebuild and unserialize it
         * — the endpoint had grown to well over a minute.
         */
        $locations = Cache::remember('taxonomy_locations', 300, function () {
            return Location::query()
                ->leftJoin('locations as parents', 'parents.id', '=', 'locations.parent_id')
                ->withCount('properties')
                ->select([
                    'locations.id',
                    'locations.name',
                    'locations.slug',
                    'locations.type',
                    'locations.latitude',
                    'locations.longitude',
                    'locations.status',
                    'locations.featured',
                    'locations.created_at',
                    'parents.slug as parent_slug',
                ])
                ->orderBy('locations.type')
                ->orderBy('locations.name')
                ->get()
                ->map(fn (Location $location): array => [
                    'id' => (string) $location->id,
                    'name' => $location->name,
                    'slug' => $location->slug,
                    'type' => $location->type->value,
                    'parent' => $location->parent_slug,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'propertyCount' => (int) $location->properties_count,
                    'status' => $location->status->value,
                    'featured' => (bool) $location->featured,
                    'createdAt' => $location->created_at->toISOString(),
                ])
                ->all();
        });

        return response()->json(['data' => $locations]);
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
        /*
         * Properties and projects hold a FK to locations, so deleting a
         * referenced row would raise a raw constraint violation. Report what
         * is blocking instead, so the admin knows what to reassign first.
         */
        $blockers = [];

        if ($children = $location->children()->count()) {
            $blockers[] = $children.' sub-location'.($children > 1 ? 's' : '');
        }

        if ($properties = $location->properties()->count()) {
            $blockers[] = $properties.' propert'.($properties > 1 ? 'ies' : 'y');
        }

        if ($projects = $location->projects()->count()) {
            $blockers[] = $projects.' project'.($projects > 1 ? 's' : '');
        }

        if ($blockers !== []) {
            return response()->json([
                'message' => "\"{$location->name}\" is still used by ".
                    implode(' and ', $blockers).
                    '. Reassign or remove those first.',
            ], 409);
        }

        $location->delete();
        Cache::forget('taxonomy_locations');

        return response()->json(null, 204);
    }
}
