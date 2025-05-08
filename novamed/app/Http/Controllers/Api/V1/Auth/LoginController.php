<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Api\V1\UserResource; // Importuj UserResource

class LoginController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate(); // Waliduje i próbuje zalogować
        $request->session()->regenerate();

        // Zwróć dane zalogowanego użytkownika za pomocą UserResource
        return response()->json([
            'user' => new UserResource(Auth::user()) // Użyj UserResource
        ]);
        // Lub zwróć 204: return response()->json(null, 204);
    }
}
