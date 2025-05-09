<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Doctor\UpdateDoctorProfileRequest;
use App\Http\Resources\Api\V1\DoctorResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DoctorProfileController extends Controller
{
    use AuthorizesRequests;

    /**
     * Wyświetl profil zalogowanego lekarza.
     */
    public function show(Request $request)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie znaleziony'], 404);
        }

        return new DoctorResource($doctor);
    }

    /**
     * Aktualizuj profil lekarza.
     */
    public function update(UpdateDoctorProfileRequest $request)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie znaleziony'], 404);
        }

        $this->authorize('update', $doctor);

        $doctor->update($request->validated());

        return new DoctorResource($doctor->fresh());
    }
}
