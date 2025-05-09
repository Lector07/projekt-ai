<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Doctor\UpdateDoctorAppointmentRequest;
use App\Http\Resources\Api\V1\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DoctorAppointmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Wyświetl listę wizyt przypisanych do lekarza.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie znaleziony'], 404);
        }

        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->with(['patient:id,name', 'procedure:id,name'])
            ->latest('appointment_datetime')
            ->paginate(15);

        return AppointmentResource::collection($appointments);
    }

    /**
     * Wyświetl szczegóły konkretnej wizyty.
     */
    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load(['patient', 'procedure']);

        return new AppointmentResource($appointment);
    }

    /**
     * Aktualizuj dane wizyty.
     */
    public function update(UpdateDoctorAppointmentRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $appointment->update($request->validated());

        return new AppointmentResource($appointment->fresh()->load(['patient', 'procedure']));
    }
}
