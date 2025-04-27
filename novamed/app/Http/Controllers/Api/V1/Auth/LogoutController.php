<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;             // <<< Dodaj import Request
use Illuminate\Support\Facades\Auth;    // <<< Dodaj import Auth
use Illuminate\Http\JsonResponse;         // <<< Dodaj import JsonResponse

class LogoutController extends Controller
{
    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request): JsonResponse // <<< ZDEFINIUJ METODĘ destroy
    {
        // Użyj guarda 'web', ponieważ sesja jest zarządzana przez middleware webowe
        Auth::guard('web')->logout();

        // Unieważnij bieżącą sesję
        $request->session()->invalidate();

        // Zregeneruj token CSRF dla bezpieczeństwa (na wypadek, gdyby sesja była jeszcze jakoś używana)
        $request->session()->regenerateToken();

        // Zwróć odpowiedź sukcesu bez treści
        return response()->json(null, 204);
    }
}
