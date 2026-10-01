<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'durationDays' => $this->duration_days,
            'listingLimit' => $this->listing_limit,
            'featuredLimit' => $this->featured_limit,
            'imageLimit' => $this->image_limit,
            'prioritySupport' => $this->priority_support,
            'analyticsAccess' => $this->analytics_access,
            'isActive' => $this->is_active,
            'sortOrder' => $this->sort_order,
        ];
    }
}
