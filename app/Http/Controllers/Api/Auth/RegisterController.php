<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        $user = $action->execute($request->toDTO());

        event(new Registered($user));

        $user->notify(new WelcomeNotification());

        Auth::login($user);

        $request->session()->regenerate();

        return (new UserResource(
            $user->load(['agency', 'agent', 'roles'])
        ))->response()->setStatusCode(201);
    }
}
