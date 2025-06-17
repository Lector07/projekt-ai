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
        $timeRange = $request->input('time_range', 'month');

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfYear();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now();

        $patientCount = User::where('role', 'patient')->count();
        $doctorUserCount = User::where('role', 'doctor')->count();
        $procedureCount = Procedure::count();

        $appointmentsQuery = Appointment::query();

        if ($useTimeFilter) {
            $appointmentsQuery->whereBetween('appointment_datetime', [$startDate, $endDate]);
        }

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
        $cancelledAppointments = $cancelledAppointments->where('status', 'cancelled') // Ogólne anulowane
        ->count();

        // Nowa statystyka: Wizyty anulowane przez pacjenta
        $cancelledByPatientCount = clone $appointmentsQuery;
        $cancelledByPatientCount = $cancelledByPatientCount->where('status', 'cancelled_by_patient')
            ->count();

        $appointmentsForAnalysis = clone $appointmentsQuery;
        $appointments = $appointmentsForAnalysis->get(['id', 'appointment_datetime', 'procedure_id']);

        // Dane miesięczne (istniejące)
        $appointmentsPerMonthArray = array_fill(0, 12, 0);
        foreach ($appointments as $appointment) {
            $month = Carbon::parse($appointment->appointment_datetime)->month - 1;
            $appointmentsPerMonthArray[$month]++;
        }

        // Dane tygodniowe (dni tygodnia)
        $appointmentsPerWeekArray = array_fill(0, 7, 0);
        foreach ($appointments as $appointment) {
            // W PHP niedziela=0, poniedziałek=1, korygujemy aby poniedziałek=0
            $dayOfWeek = (Carbon::parse($appointment->appointment_datetime)->dayOfWeek + 6) % 7;
            $appointmentsPerWeekArray[$dayOfWeek]++;
        }

        // Dane dzienne (ostatnie 7 dni)
        $appointmentsPerDayArray = array_fill(0, 7, 0);
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $last7Days[] = Carbon::now()->subDays($i)->startOfDay();
        }

        foreach ($appointments as $appointment) {
            $appointmentDate = Carbon::parse($appointment->appointment_datetime)->startOfDay();

            for ($i = 0; $i < 7; $i++) {
                if ($appointmentDate->equalTo($last7Days[$i])) {
                    $appointmentsPerDayArray[$i]++;
                    break;
                }
            }
        }

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
                'cancelled_by_patient' => $cancelledByPatientCount,
            ],
            'charts' => [
                'appointmentsPerMonth' => $appointmentsPerMonthArray,
                'appointmentsPerWeek' => $appointmentsPerWeekArray,
                'appointmentsPerDay' => $appointmentsPerDayArray,
                'popularProcedures' => $popularProcedures,
            ],
        ];

        return response()->json($stats);
    }
}
