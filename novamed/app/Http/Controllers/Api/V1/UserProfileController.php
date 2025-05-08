<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Http\Resources\Api\V1\UserResource; // Importuj UserResource

class UserProfileController extends Controller
{
    // Dodaj use AuthorizesRequests, jeśli używasz $this->authorize()
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    /**
     * Display the specified resource.
     */
    public function show(Request $request): UserResource // Zwracamy UserResource
    {
        // Middleware auth:sanctum zapewnia, że $request->user() istnieje
        $this->authorize('view', $request->user()); // Opcjonalnie, jeśli masz UserPolicy@view
        return new UserResource($request->user()); // Usunięto load('roles')
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProfileUpdateRequest $request): UserResource // Zwracamy UserResource
    {
        $user = $request->user();
        // $this->authorize('update', $user); // Autoryzacja przez Policy (jeśli zdefiniowana)

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserResource($user->fresh()); // Usunięto load('roles')
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse // Użyj dedykowanego UpdatePasswordRequest
    {
        // $this->authorize('update', $request->user()); // Autoryzacja

        // TODO: Stworzyć UpdatePasswordRequest i wstrzyknąć go tutaj
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Hasło zostało pomyślnie zaktualizowane.'], 200);
    }


    /**
     * Delete the authenticated user's account.
     */
    public function destroy(Request $request): JsonResponse
    {
        // $this->authorize('delete', $request->user()); // Autoryzacja

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::guard('web')->logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }
}
