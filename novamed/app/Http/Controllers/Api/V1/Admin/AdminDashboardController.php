<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{

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

        // Statystyki wizyt miesięczne
        $appointmentsPerMonth = Appointment::selectRaw('MONTH(appointment_datetime) as month, COUNT(*) as count')
            ->whereYear('appointment_datetime', now()->year)
            ->groupBy('month')
            ->get()
            ->keyBy('month')
            ->map(fn($item) => $item->count)
            ->toArray();

        // Najpopularniejsze procedury
        $popularProcedures = Appointment::selectRaw('procedure_id, COUNT(*) as count')
            ->groupBy('procedure_id')
            ->orderByDesc('count')
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
