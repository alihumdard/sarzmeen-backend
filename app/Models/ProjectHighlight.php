<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHighlight extends Model
{
    public $timestamps = false;

    protected $fillable = ['project_id', 'text'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
