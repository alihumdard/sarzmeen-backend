<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:expire')->dailyAt('01:00');
Schedule::command('properties:expire')->dailyAt('02:00');
Schedule::command('data:prune')->weeklyOn(1, '03:00');
Schedule::command('cache:warm')->hourly();
Schedule::command('queue:prune-batches --hours=48')->daily();
