<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'plan' => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'status' => $this->status->value,
            'isActive' => $this->isActive(),
            'startsAt' => $this->starts_at?->toIso8601String(),
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'cancelledAt' => $this->cancelled_at?->toIso8601String(),
            'daysRemaining' => $this->isActive() ? (int) now()->diffInDays($this->expires_at, false) : 0,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
