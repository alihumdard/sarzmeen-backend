<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->statefulApi();

        $middleware->trustProxies(at: '*');

        /*
         * This app is an API with a separate frontend, so there is no `login`
         * route to send an unauthenticated visitor to. Returning null keeps
         * the auth middleware from resolving one, which otherwise threw a
         * RouteNotFoundException and surfaced as a 500 instead of a 401.
         * Requests that ask for JSON -- which the frontend always does --
         * already short-circuit to 401 before this is reached.
         */
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->alias([
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'approved' => \App\Http\Middleware\EnsureAccountApproved::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Too many requests. Please try again later.',
                    'retryAfter' => $e->getHeaders()['Retry-After'] ?? null,
                ], 429);
            }
        });
    })->create();
