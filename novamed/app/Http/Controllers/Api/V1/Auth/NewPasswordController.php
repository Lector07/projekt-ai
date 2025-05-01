<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    public function store(ResetPasswordRequest $request): JsonResponse
    {
        // Dane są już zwalidowane przez ResetPasswordRequest

        // Próba resetowania hasła
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->save();
            }
        );

        // Sprawdzenie statusu i zwrócenie odpowiedzi
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => true,
                'message' => Lang::get($status)
            ], 200);
        }

        // W przypadku błędu zwracamy kod 422 z odpowiednim komunikatem
        return response()->json([
            'status' => false,
            'message' => Lang::get($status),
            'errors' => [
                'email' => [Lang::get($status)]
            ]
        ], 422);
    }
}
