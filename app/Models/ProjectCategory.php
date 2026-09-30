<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaxonomyStatus;
use Illuminate\Database\Eloquent\Model;

class ProjectCategory extends Model
{
    protected $fillable = [
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

    public function scopeActive($query)
    {
        return $query->where('status', TaxonomyStatus::Active);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
