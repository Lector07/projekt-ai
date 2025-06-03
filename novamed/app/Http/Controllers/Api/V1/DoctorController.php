<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DoctorResource; // Upewnij się, że ten import jest obecny
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Procedure;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse; // Dodaj ten import


// Możesz go potrzebować do filtrowania/sortowania w przyszłości

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Doctor::query()->with('user');

        if ($request->has('procedure_id') && !empty($request->procedure_id)) {
            $procedureId = $request->procedure_id;
            $query->whereHas('procedures', function($q) use ($procedureId) {
                $q->where('procedures.id', $procedureId);
            });
        }

        $doctors = $query->paginate(4);

        return DoctorResource::collection($doctors);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ... logika ...
        // $newDoctor = Doctor::create($request->validated());
        // return (new DoctorResource($newDoctor))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor): DoctorResource
    {
        // $doctor->load('user'); // Jeśli potrzebujesz danych użytkownika
        return new DoctorResource($doctor);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Doctor $doctor)
    {
        // ... logika ...
        // $doctor->update($request->validated());
        // return new DoctorResource($doctor->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor)
    {
        // ... logika ...
        // return response()->noContent();
    }
    public function getAvailability(Request $request, Doctor $doctor): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'procedure_id' => 'nullable|exists:procedures,id',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();
        $procedureId = $validated['procedure_id'] ?? null;

        $procedureDuration = 30; // Domyślny czas trwania w minutach
        if ($procedureId) {
            $procedure = Procedure::find($procedureId);
            // Załóżmy, że model Procedure ma pole 'duration_minutes'
            // $procedureDuration = $procedure?->duration_minutes ?? 30;
        }

        $availability = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        $existingAppointmentsDateTimes = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('appointment_datetime', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled_by_patient', 'cancelled', 'completed', 'no_show']) // Tylko aktywne/nadchodzące
            ->pluck('appointment_datetime')
            ->map(function ($datetime) {
                return Carbon::parse($datetime);
            })
            ->all();

        foreach ($period as $date) {
            if ($date->isWeekday()) {
                $daySlots = [];
                $slot = $date->copy()->setTime(9, 0, 0);
                $endOfDayWork = $date->copy()->setTime(16, 30, 0);

                while ($slot->copy()->addMinutes($procedureDuration)->lte($endOfDayWork)) {
                    $isSlotAvailable = true;

                    foreach ($existingAppointmentsDateTimes as $existingApptTime) {
                        $potentialSlotEnd = $slot->copy()->addMinutes($procedureDuration);

                        $bufferStartBeforeExisting = $existingApptTime->copy()->subHours(2);
                        $existingApptEndTime = $existingApptTime->copy()->addMinutes($procedureDuration);
                        $bufferEndAfterExisting = $existingApptEndTime->copy()->addHours(2);

                        if ($potentialSlotEnd->gt($bufferStartBeforeExisting) && $slot->lt($bufferEndAfterExisting)) {
                            $isSlotAvailable = false;
                            break;
                        }
                    }

                    if ($isSlotAvailable) {
                        $daySlots[] = $slot->format('H:i');
                    }
                    $slot->addMinutes($procedureDuration); // Przejdź do następnego potencjalnego slotu
                }

                if (count($daySlots) > 0) {
                    $availability[] = [
                        'date' => $date->format('Y-m-d'),
                        'times' => $daySlots,
                    ];
                }
            }
        }
        return response()->json(['data' => $availability]);
    }
}
