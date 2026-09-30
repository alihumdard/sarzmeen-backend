<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PropertyPurpose;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyDetailResource;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
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
            $query->whereHas('propertyType', fn ($q) => $q->where('slug', $request->string('type')->toString()));
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

        return new PropertyDetailResource($property);
    }
}
