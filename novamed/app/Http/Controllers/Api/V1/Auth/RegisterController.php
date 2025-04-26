<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function store(RegisterRequest $request){
        $validateData = $request->validated();

        $user = User::create([
            'name' => $validateData['name'],
            'email' => $validateData['email'],
            'password' => Hash::make($validateData['password']),
        ]);

        $patientRole = Role::where('name', 'patient')->first();
        if ($patientRole) {
            $user->roles()->attach($patientRole->id);
        }else{
            \Log::error("Rola 'patient' nie została znaleziona podczas rejestracji użytkownika.". $user->email);
        }

        return response()->json($user->load('roles'), 201);
    }
    //
}
