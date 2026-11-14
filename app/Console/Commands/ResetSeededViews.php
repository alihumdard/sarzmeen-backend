<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Set each property's view count to what was actually logged.
 *
 * The counts were seeded when the site was built, so the dashboard's
 * "Total Views" counted visits that never happened — 9,983 against 1,248
 * really logged. The two figures sat on the same screen disagreeing with
 * each other, and neither could be acted on.
 *
 * Only ever lowers a count: a property whose column is already at or
 * below its log is left alone, so nothing real is lost if this runs twice
 * or runs late.
 */
class ResetSeededViews extends Command
{
    protected $signature = 'views:reset-seeded {--dry-run : Show what would change and write nothing}';

    protected $description = "Bring each property's view count down to what the logs recorded";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $before = (int) Property::sum('views');
        $logged = (int) DB::table('property_view_logs')->sum('count');

        $this->line("Column total:  {$before}");
        $this->line("Logged total:  {$logged}");
        $this->line('Seeded:        '.($before - $logged));

        $affected = Property::query()
            ->whereRaw('views > (select coalesce(sum(count), 0) from property_view_logs where property_id = properties.id)')
            ->count();

        $this->line("Properties to lower: {$affected}");

        if ($dryRun) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Lower {$affected} properties to their logged counts?", false)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        /*
         * One statement rather than a loop: a property viewed while this
         * runs would otherwise have its increment read before the write
         * and overwritten after it.
         */
        DB::statement(<<<'SQL'
            update properties
            set views = coalesce((
                select sum(count) from property_view_logs
                where property_view_logs.property_id = properties.id
            ), 0)
            where views > coalesce((
                select sum(count) from property_view_logs
                where property_view_logs.property_id = properties.id
            ), 0)
        SQL);

        Cache::forget('admin_analytics_overview');

        $after = (int) Property::sum('views');

        $this->info("Done. Column total {$before} → {$after}.");

        return self::SUCCESS;
    }
}
