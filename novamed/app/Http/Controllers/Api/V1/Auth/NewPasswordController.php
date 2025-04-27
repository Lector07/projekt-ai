<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    public function store(Request $request)
    {
        // Walidacja danych wejściowych
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Próba resetowania hasła
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->save();
            }
        );

        // Sprawdzenie statusu i zwrócenie odpowiedzi
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['status' => trans($status)], 200);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
