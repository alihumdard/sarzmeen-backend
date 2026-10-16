<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /** Cache key for a whole group, so one write clears exactly one entry. */
    private static function cacheKey(string $group): string
    {
        return "site_settings_{$group}";
    }

    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        // Reads the cached group rather than querying per key: the homepage
        // asks for several keys from the same group on every render.
        return static::getGroup($group)[$key] ?? $default;
    }

    public static function setValue(string $group, string $key, mixed $value): static
    {
        $setting = static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value]
        );

        Cache::forget(static::cacheKey($group));

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return Cache::remember(
            static::cacheKey($group),
            600,
            fn () => static::where('group', $group)->pluck('value', 'key')->toArray(),
        );
    }
}
