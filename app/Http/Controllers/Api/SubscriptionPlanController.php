<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionPlanController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->get();

        return SubscriptionPlanResource::collection($plans);
    }
}
