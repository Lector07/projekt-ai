<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateAppointmentRequest;   // <<< Import
use App\Http\Resources\Api\V1\AppointmentResource;            // <<< Import
use App\Models\Appointment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // <<< Potrzebny dla $request w index()
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AdminAppointmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection // Dodano Request dla filtrowania
    {
        $this->authorize('viewAny', Appointment::class);

        // Podstawowe zapytanie z relacjami
        $query = Appointment::with(['patient:id,name', 'doctor:id,first_name,last_name', 'procedure:id,name']);

        // TODO: Dodać filtrowanie na podstawie $request->query(...)
        // np. if ($request->query('status')) { $query->where('status', $request->query('status')); }
        // np. if ($request->query('doctor_id')) { $query->where('doctor_id', $request->query('doctor_id')); }
        // np. if ($request->query('date_from')) { $query->whereDate('appointment_datetime', '>=', $request->query('date_from')); }

        $appointments = $query->latest('appointment_datetime')->paginate(15);

        return AppointmentResource::collection($appointments);
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);
        // Ładuj wszystkie potrzebne relacje
        $appointment->load(['patient', 'doctor', 'procedure']);
        return new AppointmentResource($appointment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        $this->authorize('update', $appointment);
        $validated = $request->validated(); // Głównie status i admin_notes
        $appointment->update($validated);

        // Zwróć świeży zasób z relacjami
        return new AppointmentResource($appointment->fresh()->load(['patient', 'doctor', 'procedure']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment): Response
    {
        $this->authorize('delete', $appointment);
        $appointment->delete();
        return response()->noContent();
    }
}
