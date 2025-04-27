<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// --- Dodaj te importy na górze pliku ---
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
// --- Koniec dodanych importów ---


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    // apiPrefix: 'api/v1', // Upewnij się, czy używasz tego czy prefixów w plikach tras
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleAppearance::class, // Rozważ usunięcie
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api', // <<<--- To middleware używa limitera 'api'
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    // --- DODAJ TĘ SEKCJĘ PRZED ->create() ---
    ->booted(function () {
        RateLimiter::for('api', function (Request $request) {
            // Pozwala na 60 żądań na minutę dla zalogowanego użytkownika
            // lub 10 żądań na minutę dla gościa
            return Limit::perMinute( $request->user() ? 60 : 10 )->by(
                $request->user()?->id ?: $request->ip() // Identyfikuj po ID użytkownika lub IP
            );
        });
    })
    // --- Koniec dodanej sekcji ---
    ->create();
