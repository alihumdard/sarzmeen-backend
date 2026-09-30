<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'city',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'status' => AccountStatus::class,
        ];
    }

    public function agency(): HasOne
    {
        return $this->hasOne(Agency::class);
    }

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === AccountStatus::Approved;
    }

    public function isPending(): bool
    {
        return $this->status === AccountStatus::Pending;
    }

    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::Suspended;
    }

    public function scopeByStatus($query, AccountStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->role($role);
    }
}
