<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\User; // Przykładowe modele do zliczania
use App\Models\Doctor;
use App\Models\Appointment;

class AdminDashboardController extends Controller
{
    // Nie potrzebujemy tu AuthorizesRequests, bo cała trasa jest chroniona przez auth.admin

    /**
     * Display dashboard statistics.
     */
    public function index(): JsonResponse
    {
        // Przykładowe, proste statystyki
        $patientCount = User::whereHas('roles', fn($q) => $q->where('slug', 'patient'))->count();
        $doctorCount = Doctor::count();
        $upcomingAppointments = Appointment::where('appointment_datetime', '>=', now())
            ->whereIn('status', ['booked', 'confirmed'])
            ->count();

        // TODO: Dodać bardziej złożone agregacje (np. wizyty per miesiąc, popularne procedury)

        $stats = [
            'patientCount' => $patientCount,
            'doctorCount' => $doctorCount,
            'upcomingAppointmentsCount' => $upcomingAppointments,
            // ... inne statystyki
        ];

        return response()->json($stats);
    }
}
