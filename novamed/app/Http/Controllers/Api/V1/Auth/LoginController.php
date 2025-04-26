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
        return response()->json([
            'message' => 'Zalogowano pomyślnie',
            'user' => $request->user(),
            'token' => $request->user()->createToken('auth_token')->plainTextToken,
        ]);


    }
    //
}
