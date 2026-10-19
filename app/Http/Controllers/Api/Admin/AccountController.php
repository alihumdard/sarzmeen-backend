<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Auth\ApproveAccount;
use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountUserResource;
use App\Actions\Admin\CreateUserAccount;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Creates an account directly, rather than asking the person to register
     * and then approving them. An agency can already add its own agents; this
     * gives an admin the same ability across every role.
     */
    public function store(CreateUserRequest $request, CreateUserAccount $action): JsonResponse
    {
        $user = $action->execute($request->validated());

        return response()->json([
            'data' => new UserResource($user),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::with(['roles', 'agency', 'agent.agency'])
            ->latest();

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $accounts = $query->paginate($request->integer('per_page', 15));

        return AccountUserResource::collection($accounts)->response();
    }

    public function updateStatus(
        Request $request,
        User $user,
        ApproveAccount $action
    ): JsonResponse {
        $request->validate([
            'status' => ['required', 'in:approved,rejected,suspended'],
        ]);

        $newStatus = AccountStatus::from($request->input('status'));
        $user = $action->execute($user, $request->user(), $newStatus);

        return (new AccountUserResource($user->load(['roles', 'agency', 'agent.agency'])))
            ->response();
    }
}
