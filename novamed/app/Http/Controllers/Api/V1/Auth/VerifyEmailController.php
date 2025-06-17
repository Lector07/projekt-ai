<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect('/#/login?error=invalid_verification');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/#/login?verified=already');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect('/#/login?verified=1');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email już zweryfikowany'], 422);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link weryfikacyjny wysłany']);
    }
}
