<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAppointmentRequest; // Upewnij się, że to jest właściwy FormRequest dla pacjenta
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Resources\Api\V1\AppointmentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class PatientAppointmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Autoryzacja może nie być potrzebna, jeśli pobieramy tylko wizyty zalogowanego użytkownika
        // $this->authorize('viewAny', Appointment::class);
        $user = $request->user();

        $query = $user->appointmentsAsPatient()
            ->with([
                'doctor:id,first_name,last_name,specialization', // Dodaj specialization, jeśli potrzebne na liście
                'procedure:id,name,base_price' // Dodaj base_price, jeśli potrzebne na liście
            ]);

        // Logika filtrowania np. po statusie lub datach
        if ($request->has('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        }
        if ($request->boolean('upcoming')) {
            $query->where('appointment_datetime', '>=', now())
                ->whereIn('status', ['scheduled', 'confirmed']); // Tylko te, które się odbędą
        }

        // Sortowanie
        $query->orderBy('appointment_datetime', $request->boolean('upcoming') ? 'asc' : 'desc');


        $perPage = $request->input('limit', $request->input('per_page', 10)); // Obsługa 'limit' lub 'per_page'

        // Jeśli 'limit' jest używany i nie chcemy paginacji, a tylko określoną liczbę wyników
        if ($request->has('limit') && !$request->has('page')) {
            $appointments = $query->take((int)$perPage)->get();
        } else {
            $appointments = $query->paginate((int)$perPage);
        }

        return AppointmentResource::collection($appointments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request): AppointmentResource
    {
        $validatedData = $request->validated();
        $user = $request->user();

        // Autoryzacja tworzenia (może być w FormRequest lub tutaj)
        $this->authorize('create', Appointment::class);

        $appointment = $user->appointmentsAsPatient()->create([
            'doctor_id' => $validatedData['doctor_id'],
            'procedure_id' => $validatedData['procedure_id'],
            'appointment_datetime' => $validatedData['appointment_datetime'],
            'patient_notes' => $validatedData['patient_notes'] ?? null,
            'status' => 'scheduled', // Lub 'booked', ale bądź spójny. 'scheduled' jest częstsze.
        ]);

        // Załaduj relacje, aby resource miał do nich dostęp
        $appointment->load(['doctor', 'procedure']);

        // Poprawka: Zwróć bezpośrednio zasób. Laravel ustawi status 201 automatycznie.
        return new AppointmentResource($appointment);
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);
        $appointment->load(['doctor', 'procedure', 'patient']); // Patient może nie być potrzebny, jeśli to wizyta zalogowanego pacjenta
        return new AppointmentResource($appointment);
    }

    /**
     * Update the specified resource in storage.
     * Pacjent zazwyczaj nie aktualizuje wizyty, tylko ją odwołuje.
     * Ta metoda może nie być potrzebna w tym kontrolerze.
     */
    // public function update(Request $request, Appointment $appointment)
    // {
    //     // $this->authorize('update', $appointment); // Polityka musiałaby to obsłużyć
    // }

    /**
     * Remove the specified resource from storage (or change status to cancelled).
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorize('delete', $appointment); // Upewnij się, że AppointmentPolicy@delete ma logikę np. sprawdzania czasu do wizyty

        // Zamiast twardego usuwania, rozważ zmianę statusu:
        if (in_array($appointment->status, ['scheduled', 'confirmed'])) {
            // Dodatkowa logika np. czy można jeszcze odwołać
            $appointment->status = 'cancelled_by_patient';
            $appointment->save();
            // Możesz zwrócić zaktualizowany zasób lub po prostu 200 OK z komunikatem
            return response()->json(['message' => 'Wizyta została odwołana.'], 200);
        } elseif ($appointment->status === 'cancelled_by_patient') {
            return response()->json(['message' => 'Wizyta jest już odwołana.'], 400);
        } else {
            return response()->json(['message' => 'Tej wizyty nie można odwołać.'], 400);
        }

        // Oryginalna logika twardego usuwania:
        // $appointment->delete();
        // return response()->json(null, 204);
    }

    /**
     * Check appointment availability.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_datetime' => 'required|date|after_or_equal:now',
        ]);

        $requestedDateTime = Carbon::parse($validatedData['appointment_datetime']);
        $twoHoursBefore = $requestedDateTime->copy()->subHours(2);
        $twoHoursAfter = $requestedDateTime->copy()->addHours(2);

        $conflictExists = Appointment::where('doctor_id', $validatedData['doctor_id'])
            ->whereNotIn('status', ['cancelled_by_patient', 'cancelled'])
            ->where('appointment_datetime', '>', $twoHoursBefore)
            ->where('appointment_datetime', '<', $twoHoursAfter)
            ->exists();

        if ($conflictExists) {
            return response()->json([
                'available' => false,
                'message' => 'Wybrany termin jest zbyt blisko innej wizyty tego lekarza. Proszę wybrać termin z co najmniej 2-godzinnym odstępem.'
            ], 409); // HTTP 409 Conflict
        }

        return response()->json([
            'available' => true,
        ]);
    }
}
