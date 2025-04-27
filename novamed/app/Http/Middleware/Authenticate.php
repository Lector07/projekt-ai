<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Pobiera ścieżkę, na którą użytkownik powinien zostać przekierowany, gdy nie jest uwierzytelniony.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        // Jeśli żądanie oczekuje JSON, zwróć null, aby Laravel zwrócił odpowiedź 401 Unauthorized.
        if ($request->expectsJson()) {
            return null;
        }

        // W przypadku żądań webowych można zwrócić ścieżkę logowania (jeśli istnieje).
        return route('login'); // Upewnij się, że trasa 'login' istnieje w pliku routes/web.php.
    }
}
