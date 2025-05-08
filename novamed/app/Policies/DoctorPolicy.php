<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DoctorPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Wszyscy mogą przeglądać listę lekarzy
    }

    public function view(User $user, Doctor $doctor): bool
    {
        return true; // Wszyscy mogą oglądać profile lekarzy
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin() || $user->id === $doctor->user_id;
    }

    public function delete(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin();
    }
}
