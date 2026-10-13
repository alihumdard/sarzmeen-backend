<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryCategory;
use App\Enums\UnitAvailability;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUnit extends Model
{
    protected $fillable = [
        'project_id',
        'unit_name',
        'unit_type',
        'category',
        'area',
        'min_area',
        'max_area',
        'area_unit',
        'rate_per_sqft',
        'starting_price',
        'max_price',
        'availability',
        'details',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'min_area' => 'decimal:2',
            'max_area' => 'decimal:2',
            'rate_per_sqft' => 'decimal:2',
            'starting_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'category' => InventoryCategory::class,
            'availability' => UnitAvailability::class,
            'details' => 'array',
        ];
    }

    /**
     * Derived rather than stored, so the price can never drift away from the
     * area and rate it is supposed to come from.
     */
    protected function totalPrice(): Attribute
    {
        return Attribute::get(
            fn (): float => round((float) $this->area * (float) $this->rate_per_sqft, 2),
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
