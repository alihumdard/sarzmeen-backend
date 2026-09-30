<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Property $property): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('properties.create');
    }

    public function update(User $user, Property $property): bool
    {
        if ($user->can('properties.edit.any')) {
            return true;
        }

        if (! $user->can('properties.edit.own')) {
            return false;
        }

        if ($user->hasRole('agency')) {
            return $property->agency_id === $user->agency?->id;
        }

        return $property->owner_id === $user->id
            && $property->owner_type === User::class;
    }

    public function delete(User $user, Property $property): bool
    {
        if ($user->can('properties.delete.any')) {
            return true;
        }

        if (! $user->can('properties.delete.own')) {
            return false;
        }

        if ($user->hasRole('agency')) {
            return $property->agency_id === $user->agency?->id;
        }

        return $property->owner_id === $user->id
            && $property->owner_type === User::class;
    }

    public function publish(User $user, Property $property): bool
    {
        return $user->can('properties.publish');
    }
}
