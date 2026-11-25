<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A map offered by a society owner or developer, awaiting review.
 */
class SocietyMapSubmission extends Model
{
    protected $fillable = [
        'society_name',
        'city_name',
        'location_id',
        'noc_approved',
        'noc_authority',
        'contact_name',
        'contact_phone',
        'contact_email',
        'contact_role',
        'contact_photo',
        'user_id',
        'notes',
        'image',
        'document',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'society_map_id',
        'submitted_ip',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'noc_approved' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(SocietyMap::class, 'society_map_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
