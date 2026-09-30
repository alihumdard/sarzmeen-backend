<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'question',
        'answer',
        'category',
        'status',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'order' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', ContentStatus::Published);
    }
}
