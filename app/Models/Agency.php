<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo',
        'city',
        'address',
        'description',
        'agency_type',
        'established_year',
        'phone',
        'email',
        'website',
        'verified',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'established_year' => 'integer',
            'status' => AccountStatus::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(AgencyLocation::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(AgencyService::class);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AccountStatus::Approved);
    }
}
