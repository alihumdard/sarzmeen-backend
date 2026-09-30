<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaxonomyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'status',
        'featured',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaxonomyStatus::class,
            'featured' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function propertyTypes(): HasMany
    {
        return $this->hasMany(PropertyType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', TaxonomyStatus::Active);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }
}
