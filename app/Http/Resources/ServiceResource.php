<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'outcome' => $this->outcome,
            'summary' => $this->summary,
            /* The longer text for the service's own page; may be unset. */
            'description' => $this->description,
            /* Always a list, so the page can map over it without a guard. */
            'points' => $this->points ?? [],
            'icon' => $this->icon,
            'status' => $this->status->value,
            'featured' => $this->featured,
            'position' => $this->position,
        ];
    }
}
