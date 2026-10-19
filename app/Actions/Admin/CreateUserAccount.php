<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates an account on an admin's behalf.
 *
 * Registration leaves agents and agencies pending for an admin to approve;
 * an account the admin created is already vouched for, so it is approved
 * immediately rather than needing a second pass through the approvals list.
 */
final class CreateUserAccount
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $role = UserRole::from($data['role']);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'status' => AccountStatus::Approved,
            ]);

            $user->assignRole($role->value);

            match ($role) {
                UserRole::Agency => $this->createAgency($user, $data),
                UserRole::Agent => $this->createAgent($user, $data),
                default => null,
            };

            return $user->load(['roles', 'agent.agency', 'agency']);
        });
    }

    private function createAgency(User $user, array $data): Agency
    {
        $name = $data['agencyName'] ?? $data['name'];

        return Agency::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'city' => $data['city'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'status' => AccountStatus::Approved,
        ]);
    }

    private function createAgent(User $user, array $data): Agent
    {
        return Agent::create([
            'user_id' => $user->id,
            // Optional: an agent can work independently of any agency.
            'agency_id' => $data['agencyId'] ?? null,
            'slug' => Str::slug($data['name']).'-'.Str::random(5),
            'title' => $data['title'] ?? null,
            'status' => AccountStatus::Approved,
        ]);
    }
}
