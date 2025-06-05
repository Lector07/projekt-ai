<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view their own profile or an admin can view any profile.
     */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {

        return $user->id === $model->id || $user->isAdmin();
    }


    public function delete(User $user, User $model): bool
    {
        if ($user->isAdmin() && $user->id !== $model->id) {
            return true;
        }
        return false;
    }

    public function viewDashboard(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
