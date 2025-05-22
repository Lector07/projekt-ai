<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Doctor\UpdateDoctorProfileRequest; // Zakładamy, że ten istnieje dla pól tekstowych
use App\Http\Requests\Api\V1\UpdateUserAvatarRequest;         // <<< Użyjemy tego dla avatara (lub dedykowanego)
use App\Http\Resources\Api\V1\DoctorResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse; // Dodaj, jeśli update zwraca JsonResponse
use Illuminate\Support\Facades\Storage;

class DoctorProfileController extends Controller
{
    use AuthorizesRequests;

    /**
     * Wyświetl profil zalogowanego lekarza.
     */
    public function show(Request $request): JsonResponse|DoctorResource
    {
        $doctor = $request->user()?->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie znaleziony lub nie jesteś lekarzem.'], 404);
        }

        $this->authorize('view', $doctor);

        return new DoctorResource($doctor);
    }

    /**
     */
    public function update(UpdateDoctorProfileRequest $request): JsonResponse|DoctorResource
    {
        $doctor = $request->user()?->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie znaleziony lub nie jesteś lekarzem.'], 404);
        }

        $this->authorize('update', $doctor);

        $doctor->update($request->validated());

        return new DoctorResource($doctor->fresh());
    }

    /**
     * Update the authenticated doctor's avatar.
     */
    public function updateAvatar(UpdateUserAvatarRequest $request): JsonResponse|DoctorResource // Używamy UpdateUserAvatarRequest dla spójności reguł
    {
        $user = $request->user();
        $doctor = $user?->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie znaleziony lub nie jesteś lekarzem.'], 404);
        }

        $this->authorize('update', $doctor);

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($doctor->profile_picture_path) {
                Storage::disk('public')->delete($doctor->profile_picture_path);
            }

            $path = $request->file('avatar')->store('avatars/doctors', 'public');
            $doctor->profile_picture_path = $path;
            $doctor->save();
        }

        return new DoctorResource($doctor->fresh());
    }
}
