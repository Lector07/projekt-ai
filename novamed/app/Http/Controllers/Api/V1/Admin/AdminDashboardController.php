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
use Illuminate\Support\Facades\DB;
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
        Log::info('AdminDashboardController@index reached by user: ' . $request->user()?->email);

        // Ręczne sprawdzenie polityki zamiast $this->authorize()
        if (! Gate::allows('viewDashboard', User::class)) {
            Log::warning('AdminDashboardController: User not authorized for viewDashboard');
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Log::info('AdminDashboardController: User authorized for viewDashboard');

        try {
            // Pobieranie parametrów filtrowania (opcjonalnie)
            $startDate = $request->input('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : Carbon::now()->startOfYear();

            $endDate = $request->input('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : Carbon::now();

            // Poprawione zliczanie użytkowników według kolumny role
            $patientCount = User::where('role', 'patient')->count();
            $doctorUserCount = User::where('role', 'doctor')->count();

            $procedureCount = Procedure::count();

            // Statystyki wizyt
            $totalAppointments = Appointment::whereBetween('appointment_datetime', [$startDate, $endDate])->count();
            $upcomingAppointments = Appointment::where('appointment_datetime', '>=', now())
                ->whereBetween('appointment_datetime', [$startDate, $endDate])
                ->whereIn('status', ['booked', 'confirmed'])
                ->count();
            $completedAppointments = Appointment::where('status', 'completed')
                ->whereBetween('appointment_datetime', [$startDate, $endDate])
                ->count();
            $cancelledAppointments = Appointment::where('status', 'cancelled')
                ->whereBetween('appointment_datetime', [$startDate, $endDate])
                ->count();

            // Wizyty per miesiąc
            $appointmentsPerMonthData = DB::table('appointments')
                ->selectRaw('EXTRACT(MONTH FROM appointment_datetime) as month_number, COUNT(*) as count')
                ->whereBetween('appointment_datetime', [$startDate, $endDate])
                ->groupBy(DB::raw('EXTRACT(MONTH FROM appointment_datetime)'))
                ->orderBy(DB::raw('EXTRACT(MONTH FROM appointment_datetime)'))
                ->get();

            $appointmentsPerMonthArray = array_fill(1, 12, 0);
            foreach ($appointmentsPerMonthData as $result) {
                if ($result->month_number >= 1 && $result->month_number <= 12) {
                    $appointmentsPerMonthArray[(int)$result->month_number] = (int)$result->count;
                }
            }

            // Najpopularniejsze procedury
            $popularProcedures = DB::table('appointments')
                ->join('procedures', 'appointments.procedure_id', '=', 'procedures.id')
                ->select('procedures.id as procedure_id', 'procedures.name as procedure_name', DB::raw('COUNT(appointments.id) as count'))
                ->whereBetween('appointments.appointment_datetime', [$startDate, $endDate])
                ->groupBy('procedures.id', 'procedures.name')
                ->orderByDesc('count')
                ->take(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->procedure_id,
                        'name' => $item->procedure_name,
                        'count' => (int)$item->count,
                    ];
                })
                ->all();

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

            // Dodatkowe logowanie danych JSON przed wysłaniem
            $jsonData = json_encode($stats);
            Log::info('AdminDashboardController: JSON data length: ' . strlen($jsonData));

            // Jawnie ustawiamy nagłówki JSON
            return response()->json($stats)
                ->header('Content-Type', 'application/json')
                ->header('X-Content-Type-Options', 'nosniff');

        } catch (\Exception $e) {
            Log::error('AdminDashboardController error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }
}
