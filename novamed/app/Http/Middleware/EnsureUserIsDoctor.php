<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response; // <<< Zmień import

class EnsureUserIsDoctor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response // <<< Zmień typ zwrotny
     */
    public function handle(Request $request, Closure $next): Response // <<< Zmień typ zwrotny
    {
        if ($request->user() && $request->user()->isDoctor()) {
            return $next($request); // Przepuść żądanie dalej
        }

        // Jeśli nie jest lekarzem, zwróć błąd 403
        return response()->json(['message' => 'Forbidden. Doctor access required.'], 403);
    }
}
