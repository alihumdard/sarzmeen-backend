<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'logo' => $imageService->url($this->logo ?? ''),
            'city' => $this->city,
            'address' => $this->address,
            'description' => $this->description,
            'agencyType' => $this->agency_type,
            'establishedYear' => $this->established_year,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->when($this->website, $this->website),
            'verified' => $this->verified,
            'totalAgents' => $this->whenCounted('agents', $this->agents_count ?? 0),
            'locations' => $this->whenLoaded('locations', fn () =>
                $this->locations->pluck('name')->all()
            ),
            'services' => $this->whenLoaded('services', fn () =>
                $this->services->pluck('name')->all()
            ),
            'owner' => new AgencyOwnerResource($this->whenLoaded('owner')),
        ];
    }
}
