<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Dla architektury REST API + SPA, gdzie trasy stanowe (login, register, logout)
     * są w grupie 'web' i korzystają z ciasteczek/sesji (Sanctum SPA Auth),
     * zazwyczaj NIE dodajemy tutaj ścieżek `/api/*`, ponieważ chcemy, aby
     * te stanowe endpointy API były chronione przez CSRF.
     *
     * Dodawaj tutaj tylko ścieżki, które *naprawdę* muszą być wyłączone
     * z ochrony CSRF (np. webhooki od zewnętrznych usług).
     *
     * @var array<int, string>
     */
    protected $except = [
        // Zostaw puste na razie, chyba że masz konkretny powód, aby coś wyłączyć.
    ];
}
