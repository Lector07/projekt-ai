<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use App\Models\Appointment;
use App\Models\User; // Zakładam, że User jest potrzebny do pobrania $request->user()

class DoctorDashboardController extends Controller
{
    /**
     * Pobiera dane potrzebne do wyświetlenia na dashboardzie lekarza.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse // Nazwa zmieniona na 'index'
    {
        /** @var User $user */
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Profil lekarza nie został znaleziony dla tego użytkownika.'], 404);
        }

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        $todaysAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_datetime', $today)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->with(['patient:id,name', 'procedure:id,name'])
            ->orderBy('appointment_datetime', 'asc')
            ->get();

        $tomorrowsAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_datetime', $tomorrow)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->with(['patient:id,name', 'procedure:id,name'])
            ->orderBy('appointment_datetime', 'asc')
            ->limit(5)
            ->get();

        $mapAppointmentData = function ($appointment) {
            return [
                'id' => $appointment->id,
                'time' => Carbon::parse($appointment->appointment_datetime)->format('H:i'),
                'datetime' => $appointment->appointment_datetime,
                'patient_name' => $appointment->patient->name ?? 'Brak danych pacjenta',
                'procedure_name' => $appointment->procedure->name ?? 'Brak danych zabiegu',
                'status' => $appointment->status,
            ];
        };

        return response()->json([
            'data' => [
                'doctor_name' => $doctor->first_name . ' ' . $doctor->last_name,
                'todays_appointments' => $todaysAppointments->map($mapAppointmentData),
                'tomorrows_appointments' => $tomorrowsAppointments->map($mapAppointmentData),
                'stats' => [ // Przykładowe statystyki, które możesz dodać
                    'total_appointments_today' => $todaysAppointments->count(),
                    'pending_confirmation_count' => Appointment::where('doctor_id', $doctor->id)
                        ->where('status', 'scheduled')
                        ->whereDate('appointment_datetime', '>=', $today) // Wizyty od dzisiaj oczekujące
                        ->count(),
                ]
            ]
        ]);
    }
}
