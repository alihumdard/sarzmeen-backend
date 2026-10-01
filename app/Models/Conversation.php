<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'property_id',
        'project_id',
        'subject',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function unreadCountFor(int $userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        $lastRead = $participant?->pivot->last_read_at;

        $query = $this->messages()->where('sender_id', '!=', $userId);

        if ($lastRead) {
            $query->where('created_at', '>', $lastRead);
        }

        return $query->count();
    }
}
