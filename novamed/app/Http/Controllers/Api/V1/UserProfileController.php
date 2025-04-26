<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProfileUpdateRequest; // <<< IMPORTUJ POPRAWNY REQUEST
use Illuminate\Http\Request; // Może być potrzebny, jeśli robisz coś z $request poza walidacją

class UserProfileController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        if(!$user){
            return response()->json(['message' => 'Nie znaleziono użytkownika'], 401);
        }
        $user->load('roles');
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProfileUpdateRequest $request) // <<< WSTRZYKNIJ FORM REQUEST
    {
        // Walidacja już się odbyła automatycznie dzięki Form Request.

        $user = $request->user(); // Pobierz zalogowanego użytkownika

        // Zaktualizuj użytkownika tylko zwalidowanymi danymi
        // Metoda fill() jest bezpieczniejsza niż update(), jeśli chcesz mieć pewność,
        // że tylko pola z $fillable w modelu User zostaną przypisane.
        // update() też działa, jeśli $fillable jest poprawnie zdefiniowane.
        $user->fill($request->validated());

        // Sprawdź, czy email został zmieniony PRZED zapisaniem
        if ($user->isDirty('email')) {
            $user->email_verified_at = null; // Zresetuj weryfikację emaila
        }

        // Zapisz zmiany w użytkowniku
        $user->save();

        // Zwróć zaktualizowany obiekt użytkownika (z rolami)
        // fresh() pobierze najnowsze dane z bazy, load('roles') dołączy role
        return response()->json($user->fresh()->load('roles'), 200);
    }

    // Możesz dodać inne metody, np. do zmiany hasła, zdjęcia itp.
}
