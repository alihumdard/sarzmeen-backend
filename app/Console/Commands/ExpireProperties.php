<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PropertyStatus;
use App\Models\Property;
use Illuminate\Console\Command;

class ExpireProperties extends Command
{
    protected $signature = 'properties:expire';
    protected $description = 'Mark published properties past their expiry date as expired';

    public function handle(): int
    {
        $count = Property::where('status', PropertyStatus::Published)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => PropertyStatus::Expired]);

        $this->info("Expired {$count} property listing(s).");

        return self::SUCCESS;
    }
}
