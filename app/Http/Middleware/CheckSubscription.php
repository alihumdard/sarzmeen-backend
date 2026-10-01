<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next, string $feature = ''): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return response()->json([
                'message' => 'An active subscription is required for this action.',
                'code' => 'subscription_required',
            ], 403);
        }

        if ($feature === 'analytics' && ! $subscription->plan->analytics_access) {
            return response()->json([
                'message' => 'Your current plan does not include analytics access.',
                'code' => 'feature_not_available',
            ], 403);
        }

        if ($feature === 'priority_support' && ! $subscription->plan->priority_support) {
            return response()->json([
                'message' => 'Your current plan does not include priority support.',
                'code' => 'feature_not_available',
            ], 403);
        }

        return $next($request);
    }
}
