<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyOwnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        return [
            'name' => $this->name,
            'title' => $this->agent?->title ?? 'Owner',
            'avatar' => $imageService->url($this->avatar ?? ''),
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }
}
