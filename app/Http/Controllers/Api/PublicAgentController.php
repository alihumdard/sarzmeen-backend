<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicAgentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Agent::approved()
            ->with(['user', 'agency', 'specializations', 'languages'])
            ->latest();

        if ($request->filled('city')) {
            $query->whereHas('agency', fn ($q) => $q->where('city', $request->input('city')));
        }

        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$term}%"))
                  ->orWhere('title', 'ilike', "%{$term}%")
                  ->orWhereHas('agency', fn ($a) => $a->where('name', 'ilike', "%{$term}%"));
            });
        }

        return AgentResource::collection($query->paginate($request->integer('per_page', 12)));
    }

    public function show(string $slug): JsonResponse
    {
        $agent = Agent::approved()
            ->where('slug', $slug)
            ->with(['user', 'agency', 'specializations', 'languages'])
            ->firstOrFail();

        $propertiesForSale = Property::ownedBy($agent->user)
            ->where('purpose', 'sale')
            ->where('status', 'published')
            ->count();

        $propertiesForRent = Property::ownedBy($agent->user)
            ->where('purpose', 'rent')
            ->where('status', 'published')
            ->count();

        $data = (new AgentResource($agent))->toArray(request());
        $data['propertiesForSale'] = $propertiesForSale;
        $data['propertiesForRent'] = $propertiesForRent;

        return response()->json(['data' => $data]);
    }
}
