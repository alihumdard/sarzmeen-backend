<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'agency_id',
        'slug',
        'title',
        'bio',
        'years_experience',
        'deals_closed',
        'office_address',
        'verified',
        'status',
        'invited_by',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'years_experience' => 'integer',
            'deals_closed' => 'integer',
            'status' => AccountStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function specializations(): HasMany
    {
        return $this->hasMany(AgentSpecialization::class);
    }

    public function languages(): HasMany
    {
        return $this->hasMany(AgentLanguage::class);
    }

    public function isIndependent(): bool
    {
        return $this->agency_id === null;
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AccountStatus::Approved);
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }
}
