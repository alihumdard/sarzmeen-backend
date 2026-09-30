<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSpecialization extends Model
{
    public $timestamps = false;

    protected $fillable = ['agent_id', 'name'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
