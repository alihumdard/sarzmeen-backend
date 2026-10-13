<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Sign-ins are not model changes, so they are recorded from auth events
 * rather than through the model trait.
 */
class RecordAuthActivity
{
    public function handleLogin(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('login')
            ->log('login');
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('logout')
            ->log('logout');
    }

    /**
     * Failed attempts matter for spotting someone probing an account, but the
     * password itself must never reach the log.
     */
    public function handleFailed(Failed $event): void
    {
        activity('auth')
            ->withProperties(['email' => $event->credentials['email'] ?? null])
            ->event('failed_login')
            ->log('failed_login');
    }
}
