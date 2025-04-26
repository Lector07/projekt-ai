<?php

namespace App\Http\Middleware; // <<< Upewnij się, że namespace jest poprawny

use Illuminate\Auth\Middleware\Authenticate as Middleware; // <<< Ważne: dziedziczy po bazowym middleware
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * Ta metoda jest wywoływana, gdy użytkownik NIE JEST uwierzytelniony,
     * a żądanie NIE oczekuje odpowiedzi JSON.
     * W czystym API rzadko będzie używana, ale musi istnieć.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        // Jeśli żądanie oczekuje JSON (API), zwróć null, aby Laravel zwrócił 401.
        // W przeciwnym razie (teoretycznie, dla żądań web), można by zwrócić nazwę trasy logowania.
        // Ponieważ nie masz trasy 'login' webowej, zwrócenie null jest bezpieczne.
        return null; // Zwróć null, aby uniknąć przekierowania
    }
}
