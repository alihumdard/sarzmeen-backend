<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\RegisterData;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterUser
{
    public function execute(RegisterData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'phone' => $data->phone,
                'city' => $data->city,
                'status' => $this->resolveStatus($data->role),
            ]);

            $user->assignRole($data->role->value);

            match ($data->role) {
                UserRole::Agency => $this->createAgencyProfile($user, $data),
                UserRole::Agent => $this->createAgentProfile($user, $data),
                default => null,
            };

            return $user;
        });
    }

    private function resolveStatus(UserRole $role): AccountStatus
    {
        return match ($role) {
            UserRole::User => AccountStatus::Approved,
            UserRole::Admin => AccountStatus::Approved,
            default => AccountStatus::Pending,
        };
    }

    private function createAgencyProfile(User $user, RegisterData $data): Agency
    {
        return Agency::create([
            'user_id' => $user->id,
            'name' => $data->agencyName ?? $data->name,
            'slug' => Str::slug($data->agencyName ?? $data->name) . '-' . Str::random(5),
            'city' => $data->city,
            'phone' => $data->phone,
            'email' => $data->email,
            'agency_type' => $data->agencyType,
            'status' => AccountStatus::Pending,
        ]);
    }

    private function createAgentProfile(User $user, RegisterData $data): Agent
    {
        return Agent::create([
            'user_id' => $user->id,
            'slug' => Str::slug($data->name) . '-' . Str::random(5),
            'title' => $data->title,
            'status' => AccountStatus::Pending,
        ]);
    }
}
