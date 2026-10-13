<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocument extends Model
{
    protected $fillable = [
        'project_id', 'title', 'type', 'path', 'is_public', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
