<?php

declare(strict_types=1);

namespace App\Actions\Agency;

use App\Enums\AccountStatus;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class InviteAgent
{
    public function execute(User $owner, array $data): User
    {
        return DB::transaction(function () use ($owner, $data) {
            $agency = $owner->agency;

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'city' => $agency->city,
                'status' => AccountStatus::Approved,
            ]);

            $user->assignRole('agent');

            Agent::create([
                'user_id' => $user->id,
                'agency_id' => $agency->id,
                'slug' => Str::slug($data['name']) . '-' . Str::random(5),
                'title' => $data['title'] ?? null,
                'status' => AccountStatus::Approved,
                'invited_by' => $owner->id,
            ]);

            return $user->load(['agent.agency', 'roles']);
        });
    }
}
