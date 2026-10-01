<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\UserSubscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Mark expired subscriptions as expired';

    public function handle(): int
    {
        $count = UserSubscription::where('status', SubscriptionStatus::Active)
            ->where('expires_at', '<', now())
            ->update(['status' => SubscriptionStatus::Expired]);

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
