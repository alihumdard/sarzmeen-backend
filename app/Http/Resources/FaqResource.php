<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'category' => $this->category,
            'status' => $this->status->value,
            'order' => $this->order,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
