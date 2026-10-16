<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectFloor extends Model
{
    protected $fillable = [
        'project_tower_id', 'name', 'floor_number',
        'floor_plan_image', 'floor_plan_pdf', 'description', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'floor_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function tower(): BelongsTo
    {
        return $this->belongsTo(ProjectTower::class, 'project_tower_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProjectUnit::class, 'project_floor_id');
    }

    /** "Basement 2", "Ground Floor", "8th Floor" — from the signed number. */
    public function displayName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->floor_number < 0) {
            return 'Basement '.abs($this->floor_number);
        }

        if ($this->floor_number === 0) {
            return 'Ground Floor';
        }

        $n = $this->floor_number;
        $suffix = match (true) {
            in_array($n % 100, [11, 12, 13], true) => 'th',
            $n % 10 === 1 => 'st',
            $n % 10 === 2 => 'nd',
            $n % 10 === 3 => 'rd',
            default => 'th',
        };

        return $n.$suffix.' Floor';
    }
}
