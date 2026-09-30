<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LocationType;
use App\Enums\TaxonomyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'type',
        'latitude',
        'longitude',
        'status',
        'featured',
    ];

    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'status' => TaxonomyStatus::class,
            'featured' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', TaxonomyStatus::Active);
    }

    public function scopeCities($query)
    {
        return $query->where('type', LocationType::City);
    }

    public function scopeAreas($query)
    {
        return $query->where('type', LocationType::Area);
    }
}
