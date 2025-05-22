<?php

namespace App\Policies;

use App\Models\ProcedureCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcedureCategoryPolicy
{
    use HandlesAuthorization;

    /**
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     */
    public function view(User $user, ProcedureCategory $procedureCategory): bool
    {
        return $user->isAdmin();
    }

    /**
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     */
    public function update(User $user, ProcedureCategory $procedureCategory): bool
    {
        return $user->isAdmin();
    }

    /**
     */
    public function delete(User $user, ProcedureCategory $procedureCategory): bool
    {
        return $user->isAdmin();
    }
}
