<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class AdminDashboardController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display dashboard statistics.
     */
    public function index(Request $request): JsonResponse
    {


        $useTimeFilter = $request->has('start_date') || $request->has('end_date');

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfYear();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now();

        Log::debug('Date filter enabled: ' . ($useTimeFilter ? 'yes' : 'no'));
        if ($useTimeFilter) {
            Log::debug('Date range:', ['start' => $startDate->toDateTimeString(), 'end' => $endDate->toDateTimeString()]);
        }

        // Podstawowe statystyki
        $patientCount = User::where('role', 'patient')->count();
        $doctorUserCount = User::where('role', 'doctor')->count();
        $procedureCount = Procedure::count();

        // Pobierz bazowe zapytanie wizyt
        $appointmentsQuery = Appointment::query();

        // Zastosuj filtr czasowy tylko jeśli podano parametry dat
        if ($useTimeFilter) {
            $appointmentsQuery->whereBetween('appointment_datetime', [$startDate, $endDate]);
        }

        // Statystyki wizyt - bez filtrowania czasowego, jeśli nie podano parametrów
        $totalAppointments = clone $appointmentsQuery;
        $totalAppointments = $totalAppointments->count();

        $upcomingAppointments = clone $appointmentsQuery;
        $upcomingAppointments = $upcomingAppointments->where('appointment_datetime', '>=', Carbon::now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();

        $completedAppointments = clone $appointmentsQuery;
        $completedAppointments = $completedAppointments->where('status', 'completed')
            ->count();

        $cancelledAppointments = clone $appointmentsQuery;
        $cancelledAppointments = $cancelledAppointments->where('status', 'cancelled')
            ->count();

        // Pobierz wszystkie wizyty do analizy
        $appointmentsForAnalysis = clone $appointmentsQuery;
        $appointments = $appointmentsForAnalysis->get(['id', 'appointment_datetime', 'procedure_id']);

        // Dane dla wykresu wizyt miesięcznych
        $appointmentsPerMonthArray = array_fill(0, 12, 0);

        foreach ($appointments as $appointment) {
            $month = Carbon::parse($appointment->appointment_datetime)->month - 1; // 0-indeksowane dla JS
            $appointmentsPerMonthArray[$month]++;
        }

        // Najpopularniejsze procedury
        $procedureCounts = [];
        foreach ($appointments as $appointment) {
            if ($appointment->procedure_id) {
                $procedureId = $appointment->procedure_id;
                if (!isset($procedureCounts[$procedureId])) {
                    $procedureCounts[$procedureId] = 0;
                }
                $procedureCounts[$procedureId]++;
            }
        }

        // Sortowanie i wybieranie top 5
        arsort($procedureCounts);
        $topProcedureIds = array_slice(array_keys($procedureCounts), 0, 5);

        $popularProcedures = [];
        if (!empty($topProcedureIds)) {
            $topProcedures = Procedure::whereIn('id', $topProcedureIds)->get();

            foreach ($topProcedures as $procedure) {
                if (isset($procedureCounts[$procedure->id])) {
                    $popularProcedures[] = [
                        'id' => $procedure->id,
                        'name' => $procedure->name,
                        'count' => $procedureCounts[$procedure->id]
                    ];
                }
            }

            // Sortowanie według liczby wizyt
            usort($popularProcedures, function ($a, $b) {
                return $b['count'] <=> $a['count'];
            });
        }

        $stats = [
            'users' => [
                'patientCount' => $patientCount,
                'doctorCount' => $doctorUserCount,
            ],
            'totalProcedures' => $procedureCount,
            'appointments' => [
                'total' => $totalAppointments,
                'upcoming' => $upcomingAppointments,
                'completed' => $completedAppointments,
                'cancelled' => $cancelledAppointments,
            ],
            'charts' => [
                'appointmentsPerMonth' => $appointmentsPerMonthArray,
                'popularProcedures' => $popularProcedures,
            ],
        ];

        return response()->json($stats);

    }
}
