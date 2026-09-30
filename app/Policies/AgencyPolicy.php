<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Agency;
use App\Models\User;

class AgencyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Agency $agency): bool
    {
        return true;
    }

    public function update(User $user, Agency $agency): bool
    {
        if ($user->can('agencies.manage.any')) {
            return true;
        }

        if ($user->can('agencies.manage.own')) {
            return $agency->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Agency $agency): bool
    {
        return $user->can('agencies.manage.any');
    }

    public function manageTeam(User $user, Agency $agency): bool
    {
        if ($user->can('agents.manage.any')) {
            return true;
        }

        if ($user->can('agents.invite')) {
            return $agency->user_id === $user->id;
        }

        return false;
    }
}
