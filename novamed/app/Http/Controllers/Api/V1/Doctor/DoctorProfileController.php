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
    public function show(Request $request): DoctorResource
    {
        $doctor = $request->user()->doctor()->with(['user', 'procedures'])->firstOrFail(); // Pobierz profil Doctor i załaduj procedury
        return new DoctorResource($doctor);
    }

    /**
     */
    public function update(UpdateDoctorProfileRequest $request): DoctorResource
    {
        $doctor = $request->user()->doctor;
        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie znaleziony'], 404); // Chociaż middleware powinno to wyłapać
        }

        $validated = $request->validated();

        $doctorDataToUpdate = collect($validated)->except('procedure_ids')->toArray();
        if (!empty($doctorDataToUpdate)) {
            $doctor->update($doctorDataToUpdate);
        }

        if ($request->has('procedure_ids')) {
            $doctor->procedures()->sync($validated['procedure_ids'] ?? []);
        }

        return new DoctorResource($doctor->fresh()->load('user', 'procedures'));
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

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $doctor = $user?->doctor;
        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie znaleziony lub nie jesteś lekarzem.'], 404);
        }


        if ($doctor->profile_picture_path) {
            Storage::disk('public')->delete($doctor->profile_picture_path);
            $doctor->profile_picture_path = null;
            $doctor->save();

            return response()->json(['message' => 'Zdjęcie profilowe zostało usunięte.', 'profile_picture_url' => null]);
        }

        return response()->json(['message' => 'Brak zdjęcia profilowego do usunięcia.'], 404);
    }
}
