<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization; // Możesz potrzebować tego traitu

class UserPolicy
{
    use HandlesAuthorization; // Jeśli używasz Response::allow/deny, ten trait jest przydatny

    /**
     * Determine whether the user can view any models.
     * Admin może listować wszystkich użytkowników.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     * Admin może widzieć profil każdego użytkownika.
     */
    public function view(User $user, User $model): bool // $model to użytkownik, którego profil oglądamy
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     * Admin może tworzyć użytkowników.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     * Admin może aktualizować każdego użytkownika.
     */
    public function update(User $user, User $model): bool // $model to użytkownik, którego profil aktualizujemy
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     * Admin może usuwać użytkowników, ale nie samego siebie.
     */
    public function delete(User $user, User $model): bool // $model to użytkownik, którego próbujemy usunąć
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can view the admin dashboard.
     */
    public function viewDashboard(User $user): bool // <<< DODAJ TĘ METODĘ
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
