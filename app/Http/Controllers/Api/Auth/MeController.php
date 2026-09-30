<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->load(['roles']);

        $role = $user->roles->first()?->name;

        if ($role === 'agency') {
            $user->load(['agency.locations', 'agency.services']);
            $user->agency?->loadCount('agents');
        }

        if ($role === 'agent') {
            $user->load(['agent.specializations', 'agent.languages', 'agent.agency']);
        }

        return (new UserResource($user))->response();
    }
}
