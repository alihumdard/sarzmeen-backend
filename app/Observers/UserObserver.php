<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function created(User $user): void
    {
        $this->clearStatsCache();
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('status')) {
            $this->clearStatsCache();
        }
    }

    public function deleted(User $user): void
    {
        $this->clearStatsCache();
    }

    private function clearStatsCache(): void
    {
        Cache::forget('admin_stats');
        Cache::forget('admin_dashboard_stats');
    }
}
