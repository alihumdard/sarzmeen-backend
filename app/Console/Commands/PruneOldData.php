<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PropertyViewLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneOldData extends Command
{
    protected $signature = 'data:prune {--days=180 : Days of view logs to keep}';
    protected $description = 'Prune old view logs and expired sessions';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $viewLogs = PropertyViewLog::where('date', '<', now()->subDays($days))->delete();
        $this->info("Deleted {$viewLogs} old view log(s).");

        $sessions = DB::table('sessions')
            ->where('last_activity', '<', now()->subDays(7)->timestamp)
            ->delete();
        $this->info("Deleted {$sessions} expired session(s).");

        $notifications = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays(90))
            ->delete();
        $this->info("Deleted {$notifications} old read notification(s).");

        $this->info('Prune complete.');

        return self::SUCCESS;
    }
}
