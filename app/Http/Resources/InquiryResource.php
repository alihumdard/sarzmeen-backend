<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?? '',
            'message' => $this->message,
            'source' => $this->source,
            'status' => $this->status->value,
            'property' => $this->whenLoaded('property', fn () => $this->property?->title),
            'propertyType' => $this->whenLoaded('property', fn () => $this->property?->propertyType?->name),
            'location' => $this->whenLoaded('property', fn () => $this->property?->full_location),
            'project' => $this->whenLoaded('project', fn () => $this->project?->name),
            'image' => $this->whenLoaded('property', fn () =>
                $this->property?->images?->firstWhere('is_cover', true)?->path
                ?? $this->property?->images?->first()?->path
                ?? ''
            ),
            'date' => $this->created_at->format('M d, Y h:i A'),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
