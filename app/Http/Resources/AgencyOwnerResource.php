<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyOwnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'title' => $this->agent?->title ?? 'Owner',
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }
}
