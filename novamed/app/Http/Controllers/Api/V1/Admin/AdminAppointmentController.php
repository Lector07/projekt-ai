<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use App\Http\Requests\Api\V1\Admin\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAppointmentRequest;

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
            ->with(['patient', 'doctor.user', 'procedure']);

        if ($request->has('doctor_name') && !empty($request->doctor_name)) {
            $doctorNameTerm = '%' . $request->doctor_name . '%';
            $query->whereHas('doctor', function($q) use ($doctorNameTerm) {
                $q->where('first_name', 'ilike', $doctorNameTerm)
                    ->orWhere('last_name', 'ilike', $doctorNameTerm)
                    ->orWhereHas('user', function($userDoctorQuery) use ($doctorNameTerm) {
                        $userDoctorQuery->where('name', 'ilike', $doctorNameTerm)
                            ->orWhere('email', 'ilike', $doctorNameTerm);
                    });
            });
        }

        if ($request->has('patient_name') && !empty($request->patient_name)) {
            $patientNameTerm = '%' . $request->patient_name . '%';
            $query->whereHas('patient', function($q) use ($patientNameTerm) {
                $q->where('name', 'ilike', $patientNameTerm)
                    ->orWhere('email', 'ilike', $patientNameTerm);
            });
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('appointment_datetime', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('appointment_datetime', '<=', $request->date_to);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', 'ilike', $request->status);
        }

        $query->orderBy('id', 'desc');
        
        $appointments = $query->paginate();

        return AppointmentResource::collection($appointments);
    }

    /**
     * Zapisz nową wizytę
     *
     * @param Request $request
     * @return AppointmentResource
     */
    public function store(StoreAppointmentRequest $request): AppointmentResource
    {
        $validated = $request->validated();
        $appointment = Appointment::create($validated);
        $appointment->load(['patient', 'doctor', 'procedure']);
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
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        $validated = $request->validated();

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
