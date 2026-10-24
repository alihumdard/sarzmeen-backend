<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\User;
use App\Notifications\PropertyStatusChangedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Property::with(['images', 'features', 'propertyType', 'location', 'agency'])
            ->with(['owner' => fn ($q) => $q->morphWith([User::class => ['agent.specializations', 'agent.languages']])])
            ->latest();

        /*
         * Only `properties.edit.any` sees the whole catalogue. Everyone else
         * is limited to their own listings — an agency to its team's, an
         * individual to the ones they own.
         */
        $user = $request->user();

        if (! $user->can('properties.edit.any')) {
            $agencyId = $user->agency?->id;

            $query->where(function ($q) use ($user, $agencyId) {
                $q->where(function ($own) use ($user) {
                    $own->where('owner_type', User::class)
                        ->where('owner_id', $user->id);
                });

                if ($agencyId) {
                    $q->orWhere('agency_id', $agencyId);
                }
            });
        }

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

    /**
     * Marks a listing as featured, which is what puts it in the homepage
     * block. Guarded by properties.publish, so only an admin decides what
     * the site leads with — not the agent who listed it.
     */
    public function updateFeatured(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'featured' => ['required', 'boolean'],
        ]);

        $property->featured = $validated['featured'];
        $property->save();

        return response()->json([
            'data' => [
                'id' => (string) $property->id,
                'featured' => $property->featured,
            ],
        ]);
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
