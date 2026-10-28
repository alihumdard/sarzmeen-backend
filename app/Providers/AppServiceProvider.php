<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\RecordAuthActivity;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);

        /*
         * The reset mail links to the site, not the API. Laravel builds that
         * URL from APP_URL by default, which is the backend and has no page
         * to show, so the link in the email went nowhere.
         */
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $base = rtrim((string) config('app.frontend_url'), '/');

            return $base.'/reset-password?token='.$token
                .'&email='.urlencode($user->getEmailForPasswordReset());
        });

        /*
         * Same for the verification mail. The in-app route is a POST behind
         * auth:sanctum, which a link in an email can neither send nor carry a
         * session for, so the mail gets its own signed GET that works while
         * logged out and hands the visitor back to the site afterwards.
         */
        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify.link',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );
        });

        Event::listen(Login::class, [RecordAuthActivity::class, 'handleLogin']);
        Event::listen(Logout::class, [RecordAuthActivity::class, 'handleLogout']);
        Event::listen(Failed::class, [RecordAuthActivity::class, 'handleFailed']);

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('search', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(30)->by($request->user()->id)
                : Limit::perMinute(15)->by($request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
