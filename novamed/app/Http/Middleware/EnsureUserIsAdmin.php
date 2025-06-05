<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->isAdmin()) {
            return $next($request);
        }

        return response()->json(['message' => 'Wymagany jest dostęp dla administratora.'], 403);
    }
}
