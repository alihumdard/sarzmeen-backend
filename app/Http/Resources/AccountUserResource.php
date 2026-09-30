<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->roles->first()?->name;

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?? '',
            'avatar' => $this->avatar ?? '',
            'role' => $role,
            'status' => $this->status->value,
            'city' => $this->city ?? '',
            'agency' => $this->when($role === 'agent', fn () =>
                $this->agent?->agency?->name
            ),
            'owner' => $this->when($role === 'agency', fn () =>
                $this->name
            ),
            'totalAgents' => $this->when($role === 'agency', fn () =>
                $this->agency?->agents_count ?? 0
            ),
            'listings' => 0,
            'joinedAt' => $this->created_at->toIso8601String(),
        ];
    }
}
