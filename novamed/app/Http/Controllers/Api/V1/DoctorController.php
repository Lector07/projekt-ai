<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DoctorResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Procedure;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;



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

        $perPage = (int) ($request->input('per_page', 6));
        $doctors = $query->paginate($perPage);

        return DoctorResource::collection($doctors);
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor): DoctorResource
    {
        $doctor->load('user');
        return new DoctorResource($doctor);
    }

    /**
     * Update the specified resource in storage.
     */

    public function getBookedAppointments(Request $request, Doctor $doctor): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'sometimes|date_format:Y-m-d',
            'end_date' => 'sometimes|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        //zakres daty
        $startDate = Carbon::parse($validated['start_date'] ?? Carbon::now()->startOfMonth())->startOfDay();
        $endDate = Carbon::parse($validated['end_date'] ?? Carbon::now()->endOfMonth())->endOfDay();

        //wszystkie aktywne wizyty
        $bookedAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('appointment_datetime', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled_by_patient', 'cancelled', 'completed', 'no_show'])
            ->with('procedure:id,duration_minutes')
            ->get(['id', 'appointment_datetime', 'procedure_id']);

        //mapowanie do jsona
        $formattedBooked = $bookedAppointments->map(function ($appointment) {
            $dt = Carbon::parse($appointment->appointment_datetime);
            return [
                'date' => $dt->format('Y-m-d'),
                'time' => $dt->format('H:i'),
                'end_time' => $dt->copy()->addMinutes($appointment->procedure->duration_minutes ?? 30)->format('H:i'),
                'procedure_duration' => $appointment->procedure->duration_minutes ?? 30,
            ];
        });

        return response()->json(['data' => $formattedBooked]);
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

        //domyslny czas zabiegu
        $procedureDuration = 30;
        //jak poda id procedury to pobierz czas trwania
        if ($procedureId) {
            $procedure = Procedure::find($procedureId);
            $procedureDuration = $procedure?->duration_minutes ?? 30;
        }


        $availability = [];
        $period = CarbonPeriod::create($startDate, $endDate); //okres dat do sprawdzenia

        //pobrania aktywnych wizyt ziuta
        $existingAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('appointment_datetime', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled_by_patient', 'cancalled_by_clinic', 'cancelled', 'completed', 'no_show'])
            ->with('procedure:id,duration_minutes')
            ->get();

        //przejscie po wszystkich dniach z okresu
        foreach ($period as $date) {
            //sprawdzenie czy to pon-pt
            if ($date->isWeekday()) {
                $workStartTime = '09:00';
                $workEndTime = '17:00';

                $dayStart = Carbon::parse($date->format('Y-m-d') . ' ' . $workStartTime);
                $dayEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $workEndTime);

                //pobranie zajetych segmentow
                $busySegments = $existingAppointments
                    ->filter(function($appt) use ($date) {
                        return Carbon::parse($appt->appointment_datetime)->isSameDay($date);
                    })
                    ->map(function($appt) {
                        $start = Carbon::parse($appt->appointment_datetime);
                        $appointmentDuration = $appt->procedure->duration_minutes ?? 30;
                        return [
                            'start' => $start,
                            'end' => $start->copy()->addMinutes($appointmentDuration)
                        ];
                    })
                    ->sortBy('start')
                    ->values();

                $availableSlots = [];
                $currentSlot = $dayStart->copy();

                //generowanie slota co 30 min
                // sprawdzenie czy slot nie przekracza konca dnia
                while ($currentSlot->copy()->addMinutes($procedureDuration)->lte($dayEnd)) {
                    // sprawdzenie czy slot zaczyna się na 00 albo 30
                    $minutes = (int)$currentSlot->format('i');
                    if ($minutes !== 0 && $minutes !== 30) {
                        $currentSlot->setTime(
                            $currentSlot->hour + ($minutes > 30 ? 1 : 0),
                            $minutes > 30 ? 0 : 30
                        );
                        //przeskoczenie do kolejnego slotu
                        continue;
                    }

                    //sprawdzenie czy slot nie koliduje z zajetymi segmentami
                    if (!$this->isOverlapping($currentSlot, $procedureDuration, $busySegments)) {
                        $availableSlots[] = $currentSlot->format('H:i');
                    }

                    $currentSlot->addMinutes(30);
                }

                if (count($availableSlots) > 0) {
                    $availability[] = [
                        'date' => $date->format('Y-m-d'),
                        'times' => $availableSlots,
                    ];
                }
            }
        }

        return response()->json(['data' => $availability]);
    }

    private function isOverlapping(Carbon $start, int $duration, $busySegments): bool
    {
        //obliczenie konca slotu
        $end = $start->copy()->addMinutes($duration);

        foreach ($busySegments as $segment) {
            //sprawdzenie czy segmenty koliduja
            if ($start->lt($segment['end']) && $end->gt($segment['start'])) {
                return true;
            }
        }

        return false;
    }
}
