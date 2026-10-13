<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Enums\TaxonomyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyType extends Model
{
    use RecordsActivity;

    protected array $activityFields = ['name', 'status', 'featured'];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', TaxonomyStatus::Active);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
