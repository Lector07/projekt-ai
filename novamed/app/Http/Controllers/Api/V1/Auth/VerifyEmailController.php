<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'Nieprawidłowy lub wygasły link weryfikacyjny'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email już zweryfikowany']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email został zweryfikowany']);
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
