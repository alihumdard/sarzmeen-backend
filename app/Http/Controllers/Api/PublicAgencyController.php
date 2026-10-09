<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgencyResource;
use App\Models\Agency;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicAgencyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Agency::approved()
            ->withCount('agents')
            ->with(['locations', 'services', 'owner'])
            ->latest();

        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ilike', "%{$term}%")
                  ->orWhere('city', 'ilike', "%{$term}%")
                  ->orWhere('agency_type', 'ilike', "%{$term}%");
            });
        }

        return AgencyResource::collection($query->paginate($request->integer('per_page', 12)));
    }

    public function show(string $slug): JsonResponse
    {
        $agency = Agency::approved()
            ->where('slug', $slug)
            ->withCount('agents')
            ->with(['locations', 'services', 'owner', 'agents' => fn ($q) => $q->approved()->with('user')])
            ->firstOrFail();

        $propertiesForSale = Property::where('agency_id', $agency->id)
            ->where('purpose', 'sale')
            ->where('status', 'published')
            ->count();

        $propertiesForRent = Property::where('agency_id', $agency->id)
            ->where('purpose', 'rent')
            ->where('status', 'published')
            ->count();

        $data = (new AgencyResource($agency))->toArray(request());
        $data['propertiesForSale'] = $propertiesForSale;
        $data['propertiesForRent'] = $propertiesForRent;

        return response()->json(['data' => $data]);
    }
}
