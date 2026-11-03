<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);

        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'subject' => $this->subject,
            'body' => $this->body,
            'attachment' => $this->attachment ? $imageService->url($this->attachment) : null,
            'status' => $this->status,
            'adminReply' => $this->admin_reply,
            'repliedBy' => $this->whenLoaded('repliedBy', fn () => $this->repliedBy?->name),
            'repliedAt' => $this->replied_at?->toIso8601String(),
            // Only the admin list loads the sender; the sender's own list knows who it is.
            'sender' => $this->whenLoaded('user', fn () => [
                'id' => (string) $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $this->user->getRoleNames()->first(),
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
