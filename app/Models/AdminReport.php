<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A report, suggestion or change request an agent or agency sent to the admin.
 *
 * Kept apart from conversations on purpose: this one carries a status the
 * admin works through, so it can be chased until it is resolved.
 */
class AdminReport extends Model
{
    public const TYPES = ['problem', 'suggestion', 'change_request', 'other'];

    public const STATUSES = ['new', 'in_review', 'resolved', 'declined'];

    protected $fillable = [
        'user_id',
        'type',
        'subject',
        'body',
        'attachment',
        'status',
        'admin_reply',
        'replied_by',
        'replied_at',
    ];

    protected function casts(): array
    {
        return ['replied_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
