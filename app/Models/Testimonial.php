<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'city',
        'avatar',
        'rating',
        'purchase',
        'quote',
        'status',
        'featured',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'featured' => 'boolean',
            'rating' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', ContentStatus::Published);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
