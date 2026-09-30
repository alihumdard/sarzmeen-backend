<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $agencyId = $request->user()->agency->id;

        $query = Property::forAgency($agencyId)
            ->with(['images', 'features', 'propertyType', 'location', 'owner'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return PropertyResource::collection($query->paginate(12));
    }
}
