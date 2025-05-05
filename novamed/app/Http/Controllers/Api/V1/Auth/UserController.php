<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Pobierz dane zalogowanego użytkownika.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $userData = $user->toArray();

        if (method_exists($user, 'hasRole')) {
            $userData['role'] = $user->hasRole('admin') ? 'admin' : 'user';
        }

        if (method_exists($user, 'roles')) {
            $userData['roles'] = $user->roles()->pluck('name')->toArray();
        }

        return response()->json($userData);
    }
}
