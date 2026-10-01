<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageService = app(ImageService::class);
        $userId = $request->user()->id;
        $otherParticipant = $this->participants->firstWhere('id', '!=', $userId);

        return [
            'id' => (string) $this->id,
            'subject' => $this->subject,
            'participant' => $otherParticipant ? [
                'id' => (string) $otherParticipant->id,
                'name' => $otherParticipant->name,
                'avatar' => $imageService->url($otherParticipant->avatar ?? ''),
            ] : null,
            'property' => $this->whenLoaded('property', fn () => $this->property ? [
                'id' => (string) $this->property->id,
                'title' => $this->property->title,
                'slug' => $this->property->slug,
            ] : null),
            'lastMessage' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage ? [
                'body' => str($this->latestMessage->body)->limit(80)->toString(),
                'senderId' => (string) $this->latestMessage->sender_id,
                'createdAt' => $this->latestMessage->created_at->toIso8601String(),
            ] : null),
            'unreadCount' => $this->unreadCountFor($userId),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
