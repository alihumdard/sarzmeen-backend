<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A grouping an owner made for their own videos.
 *
 * Kept per owner: an agency groups its videos the way its work is
 * organised, which is not how another agency or the site would. Both
 * owner columns null means it belongs to the site.
 */
class VideoCategory extends Model
{
    protected $fillable = ['agent_id', 'agency_id', 'name', 'slug', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /** The site's own categories, for the public guides page. */
    public function scopeEditorial(Builder $query): Builder
    {
        return $query->whereNull('agent_id')->whereNull('agency_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * A slug that is free for this owner.
     *
     * Unique per owner rather than across the table, so two agencies may
     * both have a "Walkthroughs" without one of them being refused.
     */
    public static function uniqueSlug(string $name, ?int $agentId, ?int $agencyId, ?int $ignoreId = null): string
    {
        $base = str($name)->slug()->value() ?: 'category';
        $slug = $base;
        $n = 1;

        while (
            static::where('slug', $slug)
                ->where('agent_id', $agentId)
                ->where('agency_id', $agencyId)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }
}
