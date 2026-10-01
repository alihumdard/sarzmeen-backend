<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyDetailResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComparisonController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|string',
        ]);

        $ids = array_map('intval', array_filter(explode(',', $request->string('ids')->toString())));

        if (count($ids) < 2 || count($ids) > 4) {
            return response()->json(['message' => 'Provide 2 to 4 property IDs.'], 422);
        }

        $properties = Property::whereIn('id', $ids)
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
            ->get();

        return response()->json([
            'data' => PropertyDetailResource::collection($properties),
        ]);
    }
}
