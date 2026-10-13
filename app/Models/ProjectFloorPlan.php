<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFloorPlan extends Model
{
    protected $fillable = [
        'project_id', 'name', 'floor_number', 'tower',
        'image', 'pdf', 'description', 'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
