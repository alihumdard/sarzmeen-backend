<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        if ($user->can('users.view')) {
            return true;
        }

        return $user->id === $target->id;
    }

    public function update(User $user, User $target): bool
    {
        if ($user->can('users.view')) {
            return true;
        }

        return $user->id === $target->id;
    }

    public function approve(User $user, User $target): bool
    {
        return $user->can('users.approve');
    }

    public function suspend(User $user, User $target): bool
    {
        return $user->can('users.suspend') && $user->id !== $target->id;
    }
}
