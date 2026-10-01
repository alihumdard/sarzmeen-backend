<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Notifications\PropertyStatusChangedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Property::with(['images', 'features', 'propertyType', 'location', 'owner'])
            ->latest();

        if ($request->filled('status')) {
            $status = PropertyStatus::tryFrom($request->string('status')->toString());
            if ($status) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->input('purpose'));
        }

        return PropertyResource::collection($query->paginate(15));
    }

    public function updateStatus(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:published,rejected,expired,sold'],
        ]);

        $oldStatus = $property->status->value;
        $newStatus = PropertyStatus::from($validated['status']);

        $property->status = $newStatus;

        if ($newStatus === PropertyStatus::Published && ! $property->published_at) {
            $property->published_at = now();
        }

        $property->save();

        if ($property->owner) {
            $property->owner->notify(new PropertyStatusChangedNotification($property, $oldStatus));
        }

        return response()->json([
            'data' => [
                'id' => (string) $property->id,
                'status' => $property->status->value,
            ],
        ]);
    }
}
