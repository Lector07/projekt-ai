<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Doctor\UpdateDoctorAppointmentRequest;
use App\Http\Resources\Api\V1\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorAppointmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Wyświetl listę wizyt przypisanych do lekarza.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie znaleziony.'], 404);
        }

        $query = Appointment::where('doctor_id', $doctor->id)
            ->with(['patient:id,name', 'procedure:id,name']);

        // Filtrowanie po statusie
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Filtrowanie po dacie "od"
        if ($request->filled('date_from')) {
            $query->whereDate('appointment_datetime', '>=', $request->input('date_from'));
        }

        // Filtrowanie po dacie "do"
        if ($request->filled('date_to')) {
            $query->whereDate('appointment_datetime', '<=', $request->input('date_to'));
        }

        // Wyszukiwanie po nazwisku pacjenta
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('patient', function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%"); // Jeśli masz osobne first_name, last_name
            });
        }

        // Sortowanie
        $sortBy = $request->input('sort_by', 'newest'); // Domyślnie 'newest'
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('appointment_datetime', 'asc');
                break;
            case 'patient_asc':
                // Wymaga JOIN lub bardziej złożonego sortowania po relacji
                // Dla uproszczenia, można sortować po ID pacjenta lub imieniu, jeśli jest w tabeli appointments
                // Jeśli patient.name jest ładowane, możesz próbować sortować, ale może być nieefektywne
                // $query->join('users as patients', 'appointments.patient_id', '=', 'patients.id')
                //       ->orderBy('patients.name', 'asc')
                //       ->select('appointments.*'); // Ważne, aby uniknąć konfliktu kolumn
                // Prostsze, jeśli tylko sortujesz po dacie dla nie "newest"
                $query->orderBy('appointment_datetime', 'asc'); // Domyślnie, jeśli patient_asc niezaimplementowane
                break;
            case 'patient_desc':
                // $query->join('users as patients', 'appointments.patient_id', '=', 'patients.id')
                //       ->orderBy('patients.name', 'desc')
                //       ->select('appointments.*');
                $query->orderBy('appointment_datetime', 'desc'); // Domyślnie
                break;
            case 'newest':
            default:
                $query->orderBy('appointment_datetime', 'desc');
                break;
        }

        $perPage = (int) $request->input('per_page', 10); // Pobierz per_page z requestu
        $appointments = $query->paginate($perPage);

        return AppointmentResource::collection($appointments);
    }

    /**
     * Wyświetl szczegóły konkretnej wizyty.
     */
    public function show(Request $request, Appointment $appointment): AppointmentResource
    {
        $this->authorize('viewByDoctor', $appointment);

        $appointment->load(['patient:id,name,email', 'procedure:id,name,description,base_price']);

        return new AppointmentResource($appointment);
    }

    /**
     * Aktualizuj dane wizyty.
     */
    public function update(UpdateDoctorAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {

        $validatedData = $request->validated();

        $appointment->fill($validatedData);
        $appointment->save();

        return new AppointmentResource($appointment->fresh()->load(['patient', 'procedure']));
    }
}
