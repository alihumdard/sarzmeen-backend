<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'city' => $this->city,
            'avatar' => $imageService->url($this->avatar ?? ''),
            'rating' => $this->rating,
            'purchase' => $this->purchase,
            'quote' => $this->quote,
            'status' => $this->status->value,
            'featured' => $this->featured,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
