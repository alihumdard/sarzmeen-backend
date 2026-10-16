<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTower extends Model
{
    protected $fillable = [
        'project_id', 'name', 'floors_count', 'units_count',
        'image', 'description', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'floors_count' => 'integer',
            'units_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function floors(): HasMany
    {
        return $this->hasMany(ProjectFloor::class)->orderBy('floor_number');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProjectUnit::class);
    }
}
