<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'city' => $this->city,
            'role' => $this->roles->first()?->name,
            'status' => $this->status->value,
            'emailVerified' => $this->hasVerifiedEmail(),
            'joinedAt' => $this->created_at->toIso8601String(),
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'agent' => new AgentResource($this->whenLoaded('agent')),
        ];
    }
}
