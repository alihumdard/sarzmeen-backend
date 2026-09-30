<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyFeature extends Model
{
    public $timestamps = false;

    protected $fillable = ['property_id', 'name'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
