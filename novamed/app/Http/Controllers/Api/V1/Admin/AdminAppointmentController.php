<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

class AdminAppointmentController extends Controller
{
    /**
     * Pobierz listę wszystkich wizyt z filtrowaniem
     *
     * @param Request $request
     * @return ResourceCollection
     */
    public function index(Request $request): ResourceCollection
    {
        $query = Appointment::query()
            ->with(['patient', 'doctor', 'procedure']);

        // Filtrowanie po ID lekarza
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filtrowanie po ID pacjenta
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filtrowanie po statusie
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sortowanie - domyślnie po dacie wizyty (od najnowszych)
        $query->orderBy('appointment_datetime', 'desc');

        $appointments = $query->paginate();

        return AppointmentResource::collection($appointments);
    }

    /**
     * Zapisz nową wizytę
     *
     * @param Request $request
     * @return AppointmentResource
     */
    public function store(Request $request): AppointmentResource
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'doctor_id' => 'required|exists:doctors,id',
            'procedure_id' => 'required|exists:procedures,id',
            'appointment_datetime' => 'required|date|after:now',
            'status' => 'required|in:booked,confirmed,completed,cancelled,no-show',
            'patient_notes' => 'nullable|string|max:1000',
            'doctor_notes' => 'nullable|string|max:1000',
        ]);

        $appointment = Appointment::create($validated);

        return new AppointmentResource($appointment);
    }

    /**
     * Pobierz szczegóły konkretnej wizyty
     *
     * @param Appointment $appointment
     * @return AppointmentResource
     */
    public function show(Appointment $appointment): AppointmentResource
    {
        $appointment->load(['patient', 'doctor', 'procedure']);

        return new AppointmentResource($appointment);
    }

    /**
     * Aktualizuj dane wizyty
     *
     * @param Request $request
     * @param Appointment $appointment
     * @return AppointmentResource
     */
    public function update(Request $request, Appointment $appointment): AppointmentResource
    {
        $validated = $request->validate([
            'patient_id' => 'sometimes|exists:users,id',
            'doctor_id' => 'sometimes|exists:doctors,id',
            'procedure_id' => 'sometimes|exists:procedures,id',
            'appointment_datetime' => 'sometimes|date',
            'status' => 'sometimes|in:booked,confirmed,completed,cancelled,no-show',
            'patient_notes' => 'nullable|string|max:1000',
            'doctor_notes' => 'nullable|string|max:1000',
        ]);

        $appointment->update($validated);
        $appointment->refresh();

        return new AppointmentResource($appointment);
    }

    /**
     * Usuń wizytę
     *
     * @param Appointment $appointment
     * @return Response
     */
    public function destroy(Appointment $appointment): Response
    {
        $appointment->delete();

        return response()->noContent();
    }
}
