<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Appointment;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\UserPolicy;
use App\Models\Doctor;
use App\Policies\DoctorPolicy;
use App\Models\Procedure;
use App\Policies\ProcedurePolicy;
use Illuminate\Support\Facades\Gate;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->append(\App\Http\Middleware\Cors::class);

        $middleware->web(append: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleAppearance::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'auth.doctor' => \App\Http\Middleware\EnsureUserIsDoctor::class,
        ]);

        $middleware->trustProxies(
            '*',
                Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withProviders([
        Illuminate\Filesystem\FilesystemServiceProvider::class,
    ])
    ->booted(function () {
        // Rejestracja limitów żądań
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute($request->user() ? 60 : 10)->by(
                $request->user()?->id ?: $request->ip()
            );
        });


        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Doctor::class, DoctorPolicy::class);
        Gate::policy(Procedure::class, ProcedurePolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
    })
    ->create();

