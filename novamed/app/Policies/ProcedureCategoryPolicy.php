<?php

namespace App\Policies;

use App\Models\ProcedureCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcedureCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Określ, czy użytkownik może przeglądać listę modeli.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Określ, czy użytkownik może zobaczyć model.
     */
    public function view(User $user, ProcedureCategory $procedureCategory): bool
    {
        return $user->isAdmin();
    }

    /**
     * Określ, czy użytkownik może utworzyć model.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Określ, czy użytkownik może zaktualizować model.
     */
    public function update(User $user, ProcedureCategory $procedureCategory): bool
    {
        return $user->isAdmin();
    }

    /**
     * Określ, czy użytkownik może usunąć model.
     */
    public function delete(User $user, ProcedureCategory $procedureCategory): bool
    {
        return $user->isAdmin();
    }
}
