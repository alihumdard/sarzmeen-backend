<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionPlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->get();

        return SubscriptionPlanResource::collection($plans);
    }

    public function store(SubscriptionPlanRequest $request): JsonResponse
    {
        $data = $request->validated();

        $plan = SubscriptionPlan::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'duration_days' => $data['durationDays'],
            'listing_limit' => $data['listingLimit'],
            'featured_limit' => $data['featuredLimit'],
            'image_limit' => $data['imageLimit'],
            'priority_support' => $data['prioritySupport'] ?? false,
            'analytics_access' => $data['analyticsAccess'] ?? false,
            'is_active' => $data['isActive'] ?? true,
            'sort_order' => $data['sortOrder'] ?? 0,
        ]);

        return (new SubscriptionPlanResource($plan))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SubscriptionPlan $plan): SubscriptionPlanResource
    {
        return new SubscriptionPlanResource($plan);
    }

    public function update(SubscriptionPlanRequest $request, SubscriptionPlan $plan): SubscriptionPlanResource
    {
        $data = $request->validated();

        $plan->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'duration_days' => $data['durationDays'],
            'listing_limit' => $data['listingLimit'],
            'featured_limit' => $data['featuredLimit'],
            'image_limit' => $data['imageLimit'],
            'priority_support' => $data['prioritySupport'] ?? false,
            'analytics_access' => $data['analyticsAccess'] ?? false,
            'is_active' => $data['isActive'] ?? true,
            'sort_order' => $data['sortOrder'] ?? 0,
        ]);

        return new SubscriptionPlanResource($plan);
    }

    public function destroy(SubscriptionPlan $plan): JsonResponse
    {
        if ($plan->subscriptions()->active()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a plan with active subscriptions.',
            ], 422);
        }

        $plan->delete();

        return response()->json(null, 204);
    }
}
