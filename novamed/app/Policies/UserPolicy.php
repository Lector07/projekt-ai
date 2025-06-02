<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Pozwól adminowi na wszystko (opcjonalne, ale często stosowane).
     * Ta metoda zostanie wywołana przed innymi metodami polityki.
     * Jeśli zwróci true, użytkownik ma dostęp. Jeśli zwróci false, dostęp jest zabroniony.
     * Jeśli zwróci null, sprawdzane są kolejne metody polityki.
     */
    // public function before(User $user, string $ability): ?bool
    // {
    //     if ($user->isAdmin()) {
    //         return true;
    //     }
    //     return null; // Pozwól innym metodom zdecydować dla nie-adminów
    // }

    /**
     * Determine whether the user can view any models.
     * (Lista użytkowników - zazwyczaj tylko dla admina)
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view their own profile or an admin can view any profile.
     */
    public function view(User $user, User $model): bool
    {
        // Użytkownik może zobaczyć swój własny profil
        // LUB administrator może zobaczyć dowolny profil.
        return $user->id === $model->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     * (Tworzenie użytkowników - zazwyczaj tylko dla admina)
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update their own profile or an admin can update any profile.
     */
    public function update(User $user, User $model): bool
    {
        // Użytkownik może zaktualizować swój własny profil
        // LUB administrator może zaktualizować dowolny profil.
        return $user->id === $model->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     * (Admin może usunąć innych użytkowników, ale nie siebie samego)
     */
    public function delete(User $user, User $model): bool
    {
        // Admin może usunąć innego użytkownika, ale nie może usunąć samego siebie przez ten mechanizm.
        // Użytkownik nie może sam usunąć innego użytkownika.
        // (Usuwanie własnego konta jest obsługiwane osobno w UserProfileController@destroy z walidacją hasła)
        if ($user->isAdmin() && $user->id !== $model->id) {
            return true;
        }
        return false;
    }

    // ... reszta metod bez zmian, jeśli są poprawne ...
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
