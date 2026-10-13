<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentType;
use App\Enums\ProjectFormat;
use App\Enums\ProjectType;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use RecordsActivity;
    use SoftDeletes;

    protected array $activityFields = [
        'name', 'status', 'project_type', 'project_format', 'payment_type',
        'developer_id', 'featured', 'verified',
    ];

    protected $fillable = [
        'slug',
        'name',
        'project_category_id',
        'developer_id',
        'project_type',
        'project_format',
        'payment_type',
        'developer',
        'location_id',
        'full_location',
        'status',
        'price_from',
        'total_area',
        'total_units',
        'towers_count',
        'total_floors',
        'basement_floors',
        'parking_spaces',
        'expected_completion',
        'possession_timeline',
        'description',
        'verified',
        'featured',
        'meta_title',
        'meta_description',
        'payment_terms',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'featured' => 'boolean',
            'total_units' => 'integer',
            'payment_terms' => 'array',
            'project_type' => ProjectType::class,
            'project_format' => ProjectFormat::class,
            'payment_type' => PaymentType::class,
            'towers_count' => 'integer',
            'total_floors' => 'integer',
            'basement_floors' => 'integer',
            'parking_spaces' => 'integer',
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

    public function units(): HasMany
    {
        return $this->hasMany(ProjectUnit::class)->orderBy('sort_order');
    }

    public function developerProfile(): BelongsTo
    {
        return $this->belongsTo(Developer::class, 'developer_id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ProjectFaq::class)->orderBy('sort_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->orderBy('sort_order');
    }

    public function floorPlans(): HasMany
    {
        return $this->hasMany(ProjectFloorPlan::class)->orderBy('sort_order');
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
