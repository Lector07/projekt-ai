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


        // Opcja 2: Zwróć tylko status sukcesu (często wystarcza dla SPA)
        return response()->json(['message' => 'Zalogowano pomyślnie'], 204); // 204 No Content
    }
    //
}
