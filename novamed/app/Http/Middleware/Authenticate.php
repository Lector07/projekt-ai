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
        if ($request->expectsJson()) {
            return null;
        }

        return route('login');
    }
}
