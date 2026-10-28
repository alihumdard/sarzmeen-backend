<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops an account that is still waiting on approval from changing anything.
 *
 * Agents and agencies register as pending, and until now the only thing
 * holding them back was the notice the admin panel drew — the API itself
 * accepted their writes. Reading is left open so the panel can show them
 * their dashboard while they wait.
 */
class EnsureAccountApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $request->isMethod('GET')) {
            return $next($request);
        }

        if ($user && $user->status !== AccountStatus::Approved) {
            return response()->json([
                'message' => 'Your account is still awaiting approval, so it cannot make changes yet.',
            ], 403);
        }

        return $next($request);
    }
}
