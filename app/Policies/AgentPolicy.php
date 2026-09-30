<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Agent $agent): bool
    {
        return true;
    }

    public function update(User $user, Agent $agent): bool
    {
        if ($user->can('agents.manage.any')) {
            return true;
        }

        if ($user->can('agents.manage.own')) {
            $agency = $user->agency;
            return $agency && $agent->agency_id === $agency->id;
        }

        return $agent->user_id === $user->id;
    }

    public function delete(User $user, Agent $agent): bool
    {
        if ($user->can('agents.manage.any')) {
            return true;
        }

        if ($user->can('agents.manage.own')) {
            $agency = $user->agency;
            return $agency && $agent->agency_id === $agency->id;
        }

        return false;
    }
}
