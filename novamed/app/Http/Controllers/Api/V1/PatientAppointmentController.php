<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAppointmentRequest;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class PatientAppointmentController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);
        $user = $request->user();
        $appointments = $user->appointmentsAsPatient()
            ->with(['doctor:id,first_name,last_name', 'procedure:id,name'])
            ->latest('appointment_datetime')
            ->paginate(10);

        return response()->json([
            'appointments' => $appointments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $validatedData = $request->validated();
        $user = $request->user();

        $appointment = $user->appointmentsAsPatient()->create([
            'doctor_id' => $validatedData['doctor_id'],
            'procedure_id' => $validatedData['procedure_id'],
            'appointment_datetime' => $validatedData['appointment_datetime'],
            'patient_notes' => $validatedData['patient_notes'] ?? null,
            'status' => 'booked',
        ]);

        $appointment->load([
            'doctor:id,first_name,last_name',
            'procedure:id,name',
        ]);

        return response()->json([
            'appointment' => $appointment,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        $appointment -> load(['doctor', 'procedure', 'patient']);
        return response()->json([
            'appointment' => $appointment,
        ]);
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Appointment $appointment)
    {

        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);
        $appointment -> delete();
        return response()->json(null, 204);
        //
    }

    public function checkAvailability(Request $request)
    {
        $validatedData = $request -> validate([
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_datetime' => 'required|date',
        ]);
        $isAvailable = Appointment::where('doctor_id', $validatedData['doctor_id'])
            ->where('appointment_datetime', $validatedData['appointment_datetime'])
            ->doesntExist();
        return response()->json([
            'available' => $isAvailable,
        ]);
    }
}
