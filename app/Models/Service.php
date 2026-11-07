<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /** The icon keys the front end can render. */
    public const ICONS = [
        'handshake',
        'document',
        'search',
        'globe',
        'camera',
        'chart',
    ];

    protected $fillable = [
        'slug',
        'title',
        'outcome',
        'summary',
        'points',
        'icon',
        'status',
        'featured',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'featured' => 'boolean',
            'points' => 'array',
            'position' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', ContentStatus::Published);
    }

    /**
     * The order the page shows them in: the hand-set position first, and the
     * oldest first within a position, so adding one does not reshuffle the rest.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
