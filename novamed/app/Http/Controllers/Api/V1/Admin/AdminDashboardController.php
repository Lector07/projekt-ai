<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display dashboard statistics.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewDashboard', User::class);

        // Statystyki użytkowników i lekarzy
        $patientCount = User::whereHas('roles', fn($q) => $q->where('slug', 'patient'))->count();
        $doctorCount = Doctor::count();

        // Statystyki wizyt
        $upcomingAppointments = Appointment::where('appointment_datetime', '>=', now())
            ->whereIn('status', ['booked', 'confirmed'])
            ->count();

        $totalAppointments = Appointment::count();
        $completedAppointments = Appointment::where('status', 'completed')->count();
        $cancelledAppointments = Appointment::where('status', 'cancelled')->count();

        // Statystyki wizyt miesięczne - używamy funkcji month() kompatybilnej z różnymi bazami
        $appointmentsPerMonth = Appointment::selectRaw('EXTRACT(MONTH FROM appointment_datetime) as month, COUNT(*) as count')
            ->whereRaw('EXTRACT(YEAR FROM appointment_datetime) = EXTRACT(YEAR FROM CURRENT_DATE)')
            ->groupBy(DB::raw('EXTRACT(MONTH FROM appointment_datetime)'))
            ->orderBy(DB::raw('EXTRACT(MONTH FROM appointment_datetime)'))
            ->get()
            ->pipe(function ($results) {
                $counts = array_fill(1, 12, 0);
                foreach ($results as $result) {
                    $counts[(int)$result->month] = $result->count;
                }
                return $counts;
            });

        // Najpopularniejsze procedury - upewniamy się, że kolumna procedure_id istnieje
        $popularProcedures = Appointment::select('procedure_id', DB::raw('COUNT(*) as count'))
            ->groupBy('procedure_id')
            ->orderByDesc(DB::raw('COUNT(*)'))  // Zmiana na bezpośrednie odwołanie do funkcji
            ->take(5)
            ->with('procedure:id,name,price')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->procedure_id,
                    'name' => $item->procedure->name ?? 'Nieznana procedura',
                    'count' => $item->count,
                ];
            });

        $stats = [
            'users' => [
                'patientCount' => $patientCount,
                'doctorCount' => $doctorCount,
            ],
            'appointments' => [
                'total' => $totalAppointments,
                'upcoming' => $upcomingAppointments,
                'completed' => $completedAppointments,
                'cancelled' => $cancelledAppointments,
            ],
            'charts' => [
                'appointmentsPerMonth' => $appointmentsPerMonth,
                'popularProcedures' => $popularProcedures,
            ],
        ];

        return response()->json($stats);
    }
}
