<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NearbyPlaceKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyNearbyPlace extends Model
{
    public $timestamps = false;

    protected $fillable = ['property_id', 'name', 'distance', 'kind'];

    protected function casts(): array
    {
        return [
            'kind' => NearbyPlaceKind::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
