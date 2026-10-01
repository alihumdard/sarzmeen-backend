<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'project_category_id',
        'developer',
        'location_id',
        'full_location',
        'status',
        'price_from',
        'total_area',
        'total_units',
        'description',
        'verified',
        'featured',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'featured' => 'boolean',
            'total_units' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }

    public function amenities(): HasMany
    {
        return $this->hasMany(ProjectAmenity::class);
    }

    public function paymentPlans(): HasMany
    {
        return $this->hasMany(ProjectPaymentPlan::class)->orderBy('sort_order');
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(ProjectHighlight::class);
    }

    public function plotSizes(): HasMany
    {
        return $this->hasMany(ProjectPlotSize::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }
}
