<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProfileUpdateRequest;
use App\Http\Requests\Api\V1\Auth\UpdatePasswordRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Api\V1\UpdateUserAvatarRequest;




class UserProfileController extends Controller
{
    use AuthorizesRequests;


    public function show(Request $request): UserResource
    {
        $this->authorize('view', $request->user());
        return new UserResource($request->user());
    }


    public function update(ProfileUpdateRequest $request): UserResource
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserResource($user->fresh());
    }


    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->authorize('update', $request->user());

        $validated = $request->validated();

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Hasło zostało pomyślnie zaktualizowane.'], 200);
    }


    public function destroy(Request $request): JsonResponse
    {
        $this->authorize('delete', $request->user());

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

    public function updateAvatar(UpdateUserAvatarRequest $request): UserResource
    {
        $user = $request->user();
        $this->authorize('update', $user);

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($user->profile_picture_path) {
                Storage::disk('public')->delete($user->profile_picture_path);
            }

            $path = $request->file('avatar')->store('avatars/users', 'public');
            $user->profile_picture_path = $path;
            $user->save();
        }

        return new UserResource($user->fresh());
    }


    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        if ($user->profile_picture_path) {
            Storage::disk('public')->delete($user->profile_picture_path);
            $user->profile_picture_path = null;
            $user->save();

            return response()->json([
                'message' => 'Zdjęcie profilowe zostało usunięte.',
                'avatar' => null
            ]);
        }

        return response()->json(['message' => 'Brak zdjęcia profilowego do usunięcia.'], 404);
    }
}
