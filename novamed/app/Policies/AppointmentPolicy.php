<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->role === 'doctor') {
            return true;
        }

        return true;
    }
    public function viewByDoctor(User $user, Appointment $appointment): bool
    {
        return $user->isDoctor() && $user->doctor->id === $appointment->doctor_id;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->patient_id || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPatient();
    }

    public function update(User $user, Appointment $appointment): bool
    {
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
