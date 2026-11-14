<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A day's views of one project, post, agent, agency, service or video.
 *
 * Properties are counted in their own table, which predates this one.
 */
class PageViewLog extends Model
{
    public $timestamps = false;

    /** The kinds a row may name. */
    public const KINDS = ['project', 'blog', 'agent', 'agency', 'service', 'video'];

    protected $fillable = ['viewable_type', 'viewable_id', 'date', 'count'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'count' => 'integer',
        ];
    }

    /**
     * Record one view, adding to today's row rather than making another.
     *
     * Written as an upsert so two requests arriving together cannot each
     * read the count before the other writes it.
     */
    public static function record(string $kind, int $id): void
    {
        if (! in_array($kind, self::KINDS, true)) {
            return;
        }

        static::upsert(
            [[
                'viewable_type' => $kind,
                'viewable_id' => $id,
                'date' => now()->toDateString(),
                'count' => 1,
            ]],
            ['viewable_type', 'viewable_id', 'date'],
            ['count' => DB::raw('page_view_logs.count + 1')],
        );
    }
}
