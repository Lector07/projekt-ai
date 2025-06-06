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

        $user = $request->user();

        $query = $user->appointmentsAsPatient()
            ->with([
                'doctor:id,first_name,last_name,specialization',
                'procedure:id,name,base_price'
            ]);

        if ($request->has('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        }
        if ($request->boolean('upcoming')) {
            $query->where('appointment_datetime', '>=', now())
                ->whereIn('status', ['scheduled', 'confirmed']);
        }

        $query->orderBy('appointment_datetime', $request->boolean('upcoming') ? 'asc' : 'desc');


        $perPage = $request->input('limit', $request->input('per_page', 10));

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

        $this->authorize('create', Appointment::class);

        $appointment = $user->appointmentsAsPatient()->create([
            'doctor_id' => $validatedData['doctor_id'],
            'procedure_id' => $validatedData['procedure_id'],
            'appointment_datetime' => $validatedData['appointment_datetime'],
            'patient_notes' => $validatedData['patient_notes'] ?? null,
            'status' => 'scheduled',
        ]);

        $appointment->load(['doctor', 'procedure']);

        return new AppointmentResource($appointment);
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);
        $appointment->load(['doctor', 'procedure', 'patient']);
        return new AppointmentResource($appointment);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorize('delete', $appointment);

        if (in_array($appointment->status, ['scheduled', 'confirmed'])) {
            $appointment->status = 'cancelled_by_patient';
            $appointment->save();
            return response()->json(['message' => 'Wizyta została odwołana.'], 200);
        } elseif ($appointment->status === 'cancelled_by_patient') {
            return response()->json(['message' => 'Wizyta jest już odwołana.'], 400);
        } else {
            return response()->json(['message' => 'Tej wizyty nie można odwołać.'], 400);
        }

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
            ], 409);
        }

        return response()->json([
            'available' => true,
        ]);
    }
}
