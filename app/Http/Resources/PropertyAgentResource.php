<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyAgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $owner = $this->resource;

        if ($owner instanceof User) {
            $agent = $owner->agent;

            if ($agent) {
                return $this->agentShape($owner, $agent);
            }

            return [
                'id' => (string) $owner->id,
                'slug' => '',
                'name' => $owner->name,
                'title' => 'Owner',
                'avatar' => $owner->avatar ?? '',
                'phone' => $owner->phone,
            ];
        }

        return [
            'id' => (string) $owner->id,
            'slug' => '',
            'name' => (string) ($owner->name ?? ''),
            'title' => '',
            'avatar' => '',
        ];
    }

    private function agentShape(User $user, $agent): array
    {
        return [
            'id' => (string) $user->id,
            'slug' => $agent->slug,
            'name' => $user->name,
            'title' => $agent->title ?? 'Agent',
            'avatar' => $user->avatar ?? '',
            'phone' => $user->phone,
            'whatsapp' => $user->phone,
            'verified' => $this->when($agent->verified, true),
            'bio' => $this->when($agent->bio, $agent->bio),
            'yearsExperience' => $this->when($agent->years_experience, $agent->years_experience),
            'dealsClosed' => $this->when($agent->deals_closed, $agent->deals_closed),
            'email' => $user->email,
            'officeAddress' => $this->when($agent->office_address, $agent->office_address),
            'specializations' => $agent->relationLoaded('specializations')
                ? $agent->specializations->pluck('name')->all()
                : [],
            'languages' => $agent->relationLoaded('languages')
                ? $agent->languages->pluck('name')->all()
                : [],
        ];
    }
}
