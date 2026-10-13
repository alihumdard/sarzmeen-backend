<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $agencyUser = User::updateOrCreate(
            ['email' => 'agency@sarzameen.com'],
            [
                'name' => 'Test Agency',
                'password' => bcrypt('password'),
                'status' => AccountStatus::Approved,
                'phone' => '+92 300 1111111',
                'email_verified_at' => now(),
            ],
        );
        $agencyUser->syncRoles(['agency']);

        $agency = Agency::updateOrCreate(
            ['user_id' => $agencyUser->id],
            [
                'name' => 'Test Agency',
                'slug' => 'test-agency',
                'email' => 'agency@sarzameen.com',
                'phone' => '+92 300 1111111',
                'city' => 'Lahore',
                'status' => AccountStatus::Approved,
                'verified' => true,
            ],
        );

        $agentUser = User::updateOrCreate(
            ['email' => 'agent@sarzameen.com'],
            [
                'name' => 'Test Agent',
                'password' => bcrypt('password'),
                'status' => AccountStatus::Approved,
                'phone' => '+92 300 2222222',
                'email_verified_at' => now(),
            ],
        );
        $agentUser->syncRoles(['agent']);

        Agent::updateOrCreate(
            ['user_id' => $agentUser->id],
            [
                'agency_id' => $agency->id,
                'slug' => 'test-agent',
                'title' => 'Senior Property Consultant',
                'verified' => true,
                'status' => AccountStatus::Approved,
            ],
        );

        $user = User::updateOrCreate(
            ['email' => 'user@sarzameen.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'status' => AccountStatus::Approved,
                'phone' => '+92 300 3333333',
                'email_verified_at' => now(),
            ],
        );
        $user->syncRoles(['user']);
    }
}
