<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category->slug,
            'propertyCount' => $this->whenCounted('properties', fn () => $this->properties_count, 0),
            'status' => $this->status->value,
            'featured' => $this->featured,
            'createdAt' => $this->created_at->toISOString(),
        ];
    }
}
