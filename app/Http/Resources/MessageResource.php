<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        return [
            'id' => (string) $this->id,
            'body' => $this->body,
            'attachment' => $this->attachment ? $imageService->url($this->attachment) : null,
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => (string) $this->sender->id,
                'name' => $this->sender->name,
                'avatar' => $imageService->url($this->sender->avatar ?? ''),
            ]),
            'isOwn' => $this->sender_id === $request->user()->id,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
