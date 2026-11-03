<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedPaymentPlan extends Model
{
    protected $fillable = ['user_id', 'title', 'inputs', 'total_price', 'net_total'];

    protected function casts(): array
    {
        return [
            'inputs' => 'array',
            'total_price' => 'decimal:2',
            'net_total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
