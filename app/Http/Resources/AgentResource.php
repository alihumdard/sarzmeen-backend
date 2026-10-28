<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'title' => $this->title,
            'avatar' => $this->whenLoaded('user', fn () => $imageService->url($this->user->avatar ?? '')),
            'phone' => $this->whenLoaded('user', fn () => $this->user->phone),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'bio' => $this->bio,
            'yearsExperience' => $this->years_experience,
            'dealsClosed' => $this->deals_closed,
            'officeAddress' => $this->office_address,
            'verified' => $this->verified,
            'specializations' => $this->whenLoaded('specializations', fn () =>
                $this->specializations->pluck('name')->all()
            ),
            'languages' => $this->whenLoaded('languages', fn () =>
                $this->languages->pluck('name')->all()
            ),
            /*
             * Only when the agency is really there. Keying off agency_id
             * alone shipped a nameless card whenever the row behind it had
             * gone, which read as "View Agency Profile" with no agency.
             */
            'agency' => $this->when(
                $this->relationLoaded('agency') && $this->agency !== null,
                fn () => [
                    'id' => (string) $this->agency_id,
                    'name' => $this->agency->name,
                    'slug' => $this->agency->slug,
                ],
            ),
        ];
    }
}
