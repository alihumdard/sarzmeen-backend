<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\SocietyMapResource;
use App\Models\Location;
use App\Models\Property;
use App\Models\SocietyMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The public society-map pages.
 *
 * Maps are grouped by city, and a city here is one that actually has a
 * published map: listing all eight would send a reader to empty pages.
 */
class SocietyMapController extends Controller
{
    /**
     * The cities, each with how many maps it holds.
     */
    public function cities(): JsonResponse
    {
        $counts = SocietyMap::published()
            ->whereNotNull('city_id')
            ->selectRaw('city_id, count(*) as total')
            ->groupBy('city_id')
            ->pluck('total', 'city_id');

        if ($counts->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $cities = Location::whereIn('id', $counts->keys())
            ->orderByDesc('featured')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $cities->map(fn (Location $city) => [
                'id' => (string) $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'mapCount' => (int) $counts[$city->id],
            ])->all(),
        ]);
    }

    /**
     * Every published map, or those of one city.
     */
    public function index(Request $request): JsonResponse
    {
        $maps = SocietyMap::published()
            ->with(['location', 'city'])
            ->when($request->filled('city'), function ($q) use ($request) {
                $slug = $request->string('city')->toString();
                $q->whereHas('city', fn ($c) => $c->where('slug', $slug));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where(fn ($w) => $w->where('title', 'ilike', $term)
                    ->orWhere('summary', 'ilike', $term)
                    ->orWhereHas('location', fn ($l) => $l->where('name', 'ilike', $term)));
            })
            ->ordered()
            ->paginate($request->integer('per_page', 24));

        return SocietyMapResource::collection($maps)->response();
    }

    /**
     * One map, with its write-up, its blogs and the society's listings.
     */
    public function show(string $slug): JsonResponse
    {
        $map = SocietyMap::published()
            ->with([
                'location',
                'city',
                'blogs' => fn ($q) => $q->where('status', 'published')
                    ->with('category')
                    ->orderByDesc('published_at')
                    ->limit(6),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $map->increment('views');

        /* The society's own listings. Properties point at a location, so
           these come from the map's society rather than from any field
           the map itself would have to carry. */
        $properties = Property::query()
            ->where('status', 'published')
            ->where('location_id', $map->location_id)
            /* `owner` feeds the agent block on a property card, which
               reads its slug without checking: leaving it off crashed
               the page rather than hiding the block. */
            ->with(['location', 'images', 'propertyType', 'owner.agent'])
            ->latest()
            ->limit(8)
            ->get();

        return response()->json([
            'data' => (new SocietyMapResource($map))->resolve(),
            'properties' => PropertyResource::collection($properties)->resolve(),
        ]);
    }

    /**
     * Count a download, then hand back where the file is.
     *
     * The browser could fetch the file directly, but then nobody would
     * know a map was ever downloaded, which is the one number an editor
     * will ask about.
     */
    public function download(string $slug): JsonResponse
    {
        $map = SocietyMap::published()->where('slug', $slug)->firstOrFail();

        $map->increment('downloads');

        $resource = (new SocietyMapResource($map))->resolve();

        /* Keep whatever was uploaded: calling a JPG ".png" makes a file
           some viewers refuse to open. */
        $source = $map->document ?: $map->image;
        $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'png';

        return response()->json([
            'url' => $resource['download'],
            'filename' => $map->slug.'.'.strtolower($extension),
        ]);
    }
}
