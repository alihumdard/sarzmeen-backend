<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyService extends Model
{
    public $timestamps = false;

    protected $fillable = ['agency_id', 'name'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
