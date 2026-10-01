<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'reference' => $this->reference,
            'amount' => (float) $this->amount,
            'method' => $this->method->value,
            'status' => $this->status->value,
            'transactionId' => $this->transaction_id,
            'notes' => $this->notes,
            'subscription' => new UserSubscriptionResource($this->whenLoaded('subscription')),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => (string) $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
