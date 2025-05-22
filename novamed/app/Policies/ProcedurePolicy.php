<?php

namespace App\Policies;

use App\Models\Procedure;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProcedurePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Procedure $procedure): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Procedure $procedure): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Procedure $procedure): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Procedure $procedure): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Procedure $procedure): bool
    {
        return $user->isAdmin();
    }
}
