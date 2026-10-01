<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AreaUnit;
use App\Enums\ConditionStatus;
use App\Enums\Furnishing;
use App\Enums\ListedBy;
use App\Enums\PropertyPurpose;
use App\Enums\PropertyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'reference',
        'title',
        'headline',
        'description',
        'owner_id',
        'owner_type',
        'agency_id',
        'property_type_id',
        'project_id',
        'location_id',
        'full_location',
        'purpose',
        'price',
        'negotiable',
        'area_value',
        'area_unit',
        'beds',
        'baths',
        'living_rooms',
        'kitchens',
        'car_parking',
        'floors',
        'furnishing',
        'property_condition',
        'listed_by',
        'status',
        'featured',
        'verified',
        'views',
        'published_at',
        'expires_at',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => PropertyPurpose::class,
            'price' => 'decimal:2',
            'negotiable' => 'boolean',
            'area_value' => 'decimal:2',
            'area_unit' => AreaUnit::class,
            'furnishing' => Furnishing::class,
            'property_condition' => ConditionStatus::class,
            'listed_by' => ListedBy::class,
            'status' => PropertyStatus::class,
            'featured' => 'boolean',
            'verified' => 'boolean',
            'views' => 'integer',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function coverImage(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->where('is_cover', true)->limit(1);
    }

    public function features(): HasMany
    {
        return $this->hasMany(PropertyFeature::class);
    }

    public function nearbyPlaces(): HasMany
    {
        return $this->hasMany(PropertyNearbyPlace::class);
    }

    public function formattedArea(): string
    {
        $value = rtrim(rtrim(number_format((float) $this->area_value, 2), '0'), '.');
        $unit = match ($this->area_unit) {
            AreaUnit::Marla => 'Marla',
            AreaUnit::Kanal => 'Kanal',
            AreaUnit::Sqft => 'Sqft',
            AreaUnit::Sqyd => 'Sq. Yd.',
        };

        return "{$value} {$unit}";
    }

    public function generateReference(): string
    {
        return 'SZ-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function scopePublished($query)
    {
        return $query->where('status', PropertyStatus::Published);
    }

    public function scopeForPurpose($query, PropertyPurpose $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeOwnedBy($query, User $user)
    {
        return $query->where('owner_type', User::class)->where('owner_id', $user->id);
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }
}
