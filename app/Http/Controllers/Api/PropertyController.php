<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PropertyPurpose;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyDetailResource;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\PropertyViewLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Property::published()
            ->with(['images', 'features', 'propertyType', 'location', 'owner.agent.specializations', 'owner.agent.languages']);

        if ($request->filled('purpose')) {
            $purpose = PropertyPurpose::tryFrom($request->string('purpose')->toString());
            if ($purpose) {
                $query->forPurpose($purpose);
            }
        }

        if ($request->filled('city')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('slug', $request->string('city')->toString())
                    ->orWhereHas('parent', fn ($p) => $p->where('slug', $request->string('city')->toString()));
            });
        }

        if ($request->filled('type')) {
            /*
             * The sidebar groups several slugs under one label — "House" also
             * covers upper and lower portions, a farm house and so on — so a
             * comma-separated list has to match any of them.
             */
            $types = array_filter(explode(',', $request->string('type')->toString()));

            $query->whereHas('propertyType', fn ($q) => $q->whereIn('slug', $types));
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float) $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float) $request->input('price_max'));
        }

        if ($request->filled('beds')) {
            $query->where('beds', '>=', (int) $request->input('beds'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereRaw(
                "to_tsvector('english', coalesce(title, '') || ' ' || coalesce(description, '') || ' ' || coalesce(full_location, '')) @@ plainto_tsquery('english', ?)",
                [$search],
            );
        }

        if ($request->filled('area_min')) {
            $query->where('area_value', '>=', (float) $request->input('area_min'));
        }

        if ($request->filled('area_max')) {
            $query->where('area_value', '<=', (float) $request->input('area_max'));
        }

        if ($request->filled('baths')) {
            $query->where('baths', '>=', (int) $request->input('baths'));
        }

        if ($request->filled('agent')) {
            $query->whereHas('owner', fn ($q) => $q->whereHas('agent', fn ($a) => $a->where('slug', $request->input('agent'))));
        }

        if ($request->filled('agency')) {
            $query->where('agency_id', $request->input('agency'));
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }

        $sortField = match ($request->input('sort')) {
            'price_asc' => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            'oldest' => ['published_at', 'asc'],
            default => ['published_at', 'desc'],
        };

        $query->orderBy($sortField[0], $sortField[1]);

        $properties = $query->paginate($request->integer('per_page', 12));

        return PropertyResource::collection($properties);
    }

    /**
     * Live counts for the listing sidebar.
     *
     * These were hardcoded in the frontend (House 5,299 against 9 real
     * listings), so the sidebar contradicted the result count beside it.
     *
     * Each group maps to several type slugs, matching how the sidebar
     * presents them.
     */
    public function facets(Request $request): \Illuminate\Http\JsonResponse
    {
        $groups = [
            'house' => ['house', 'villa', 'townhouse', 'duplex', 'upper-portion', 'lower-portion', 'farm-house'],
            'plot' => ['residential-plot', 'commercial-plot', 'agricultural-land', 'industrial-land', 'plot-file'],
            'flat' => ['flat-apartment', 'penthouse', 'room'],
            'commercial' => ['shop', 'office', 'showroom', 'factory', 'warehouse', 'building', 'commercial-plaza', 'commercial-floor', 'commercial-unit', 'corporate-office', 'high-rise-office', 'high-rise-shop', 'high-rise-showroom', 'restaurant', 'food-court'],
            'apartment' => ['studio-apartment', 'two-bed-apartment', 'three-bed-apartment', '3-bed-apartment', 'four-bed-apartment', 'luxury-apartment', 'serviced-apartment', 'hotel-apartment', 'high-rise-penthouse', 'duplex-penthouse'],
        ];

        /*
         * An unrecognised purpose must not reach the query: passing one
         * through matched nothing and reported every count as zero, which
         * reads as "no listings" rather than "bad filter". The listing
         * endpoint already discards one, so this matches it.
         */
        $purpose = $request->filled('purpose')
            ? PropertyPurpose::tryFrom($request->string('purpose')->toString())
            : null;

        // One grouped query rather than five counts.
        // Qualified, since the join brings a second `status` column into scope.
        $counts = Property::query()
            ->where('properties.status', PropertyStatus::Published)
            ->join('property_types', 'property_types.id', '=', 'properties.property_type_id')
            ->when($purpose, fn ($q) => $q->where('properties.purpose', $purpose))
            ->selectRaw('property_types.slug as slug, count(*) as total')
            ->groupBy('property_types.slug')
            ->pluck('total', 'slug');

        $types = [];

        foreach ($groups as $key => $slugs) {
            $types[$key] = collect($slugs)->sum(fn ($slug) => (int) ($counts[$slug] ?? 0));
        }

        return response()->json([
            'data' => [
                'types' => $types,
                'total' => Property::published()
                    ->when($purpose, fn ($q) => $q->where('purpose', $purpose))
                    ->count(),
            ],
        ]);
    }

    public function show(string $slug): PropertyDetailResource
    {
        $property = Property::where('slug', $slug)
            ->where('status', PropertyStatus::Published)
            ->with([
                'images',
                'features',
                'nearbyPlaces',
                'propertyType',
                'location',
                'owner.agent.specializations',
                'owner.agent.languages',
            ])
            ->firstOrFail();

        $property->increment('views');

        PropertyViewLog::upsert(
            [['property_id' => $property->id, 'date' => now()->toDateString(), 'count' => 1]],
            ['property_id', 'date'],
            ['count' => \Illuminate\Support\Facades\DB::raw('property_view_logs.count + 1')],
        );

        return new PropertyDetailResource($property);
    }
}
