<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Kontroler filtruje
    }

    public function view(User $user, Appointment $appointment): bool
    {
        // Użyj isAdmin() lub hasRole()
        return $user->id === $appointment->patient_id || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        // Użyj isPatient() lub hasRole()
        return $user->isPatient();
    }

    public function update(User $user, Appointment $appointment): bool
    {
        // Użyj isAdmin() lub hasRole()
        return $user->isAdmin();
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        if ($appointment->appointment_datetime->isPast() && !$user->isAdmin()) { return false; }
        return $user->id === $appointment->patient_id || $user->isAdmin();
    }
    public function restore(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin();
    }
}
