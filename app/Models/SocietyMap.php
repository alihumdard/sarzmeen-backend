<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BlogStatus;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A society's map page.
 *
 * Only an administrator makes one, so it has no review states: it is a
 * draft until published, the way a blog is.
 */
class SocietyMap extends Model
{
    use RecordsActivity;

    protected array $activityFields = ['title', 'status', 'location_id'];

    protected $fillable = [
        'location_id',
        'city_id',
        'title',
        'slug',
        'image',
        'document',
        'summary',
        'description',
        'status',
        'featured',
        'sort_order',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BlogStatus::class,
            'featured' => 'boolean',
            'sort_order' => 'integer',
            'views' => 'integer',
            'downloads' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'blog_society_map');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', BlogStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'city_id');
    }

    /**
     * Walk up to the city a location sits under.
     *
     * A society sits under an area, which sits under a city, so the city
     * is a grandparent as often as a parent. Called when a map is saved,
     * and the answer stored in `city_id`, so a listing page can filter by
     * city without climbing the tree for every row.
     */
    public static function resolveCity(Location $location): ?Location
    {
        $node = $location;
        /* The tree is three deep by design; the cap is only so bad data
           cannot spin this forever. */
        $hops = 0;

        while ($node !== null && $hops++ < 10) {
            if ($node->type?->value === 'city') {
                return $node;
            }

            $node = $node->parent;
        }

        return null;
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'map';
        $slug = $base;
        $n = 2;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
