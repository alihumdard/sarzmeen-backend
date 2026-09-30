<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ApproveAccount
{
    public function execute(User $target, User $admin, AccountStatus $newStatus): User
    {
        return DB::transaction(function () use ($target, $admin, $newStatus) {
            $target->update([
                'status' => $newStatus,
                'approved_at' => $newStatus === AccountStatus::Approved ? now() : null,
                'approved_by' => $newStatus === AccountStatus::Approved ? $admin->id : null,
            ]);

            if ($target->agency) {
                $target->agency->update(['status' => $newStatus]);
            }

            if ($target->agent) {
                $target->agent->update(['status' => $newStatus]);
            }

            return $target->refresh();
        });
    }
}
