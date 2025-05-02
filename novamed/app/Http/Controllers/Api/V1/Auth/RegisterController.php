<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request; // Brakujący import
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // Brakujący import

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Dodawanie ról lub inne operacje

            DB::commit();
            return response()->json(['message' => 'Użytkownik zarejestrowany pomyślnie'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Błąd rejestracji', 'error' => $e->getMessage()], 500);
        }
    }
}
