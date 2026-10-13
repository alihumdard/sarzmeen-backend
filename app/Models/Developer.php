<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Developer extends Model
{
    use RecordsActivity;

    protected array $activityFields = ['name', 'phone', 'email', 'website', 'verified'];

    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'logo', 'description',
        'phone', 'email', 'website', 'address', 'verified',
    ];

    protected function casts(): array
    {
        return ['verified' => 'boolean'];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
