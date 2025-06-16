<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Lang;

class ForgotPasswordLinkController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'Podany adres e-mail nie został odnaleziony w systemie.',
        ]);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('FRONTEND_URL', 'http://127.0.0.1:8000') .
                '/reset-password/' . $token .
                '?email=' . urlencode($user->getEmailForPasswordReset());
        });

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'status' => $status === Password::RESET_LINK_SENT,
            'message' => $status === Password::RESET_LINK_SENT ? 'Link do resetowania hasła został wysłany na podany adres e-mail.' : Lang::get($status),
        ], $status === Password::RESET_LINK_SENT ? 200 : 422);
    }
}
