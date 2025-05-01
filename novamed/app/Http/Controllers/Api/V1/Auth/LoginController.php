<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(LoginRequest $request){
        $request->authenticate();
        $request->session()->regenerate();

        // Zwróć dane użytkownika wraz z rolami
        return response()->json([
            'user' => $request->user()->load('roles')
        ], 200);
    }
    //
}
