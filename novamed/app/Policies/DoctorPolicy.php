<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;
// usunięto import Response, bo nie jest używany do zwracania Response::allow/deny

class DoctorPolicy
{
    /**
     * Determine whether the user can view any models.
     * Lista lekarzy jest publiczna, ale kontroler admina będzie miał swoją logikę.
     * Dla publicznego listowania lekarzy ta polityka może nie być wywoływana,
     * lub jeśli jest, to każdy zalogowany użytkownik (a nawet gość, jeśli masz before()) może ją widzieć.
     * Na razie ustawmy, że admin może listować przez AdminDoctorController.
     */
    public function viewAny(User $user): bool
    {
        // To jest wywoływane przez AdminDoctorController@index
        return $user->isAdmin();
        // Jeśli masz publiczny endpoint /api/v1/doctors, który używa tej polityki (co jest rzadkie dla viewAny),
        // musiałbyś to inaczej obsłużyć lub dodać metodę before().
        // Zazwyczaj publiczna lista nie przechodzi przez politykę w ten sposób.
    }

    /**
     * Determine whether the user can view the model.
     * Publiczne API /api/v1/doctors/{doctor} powinno być dostępne dla wszystkich.
     * Admin też może widzieć. Lekarz może widzieć swój profil.
     * Trzeba rozważyć, gdzie ta metoda jest wywoływana.
     * Jeśli to jest dla AdminDoctorController@show:
     */
    public function view(User $user, Doctor $doctor): bool
    {
        // Admin może widzieć każdego lekarza.
        // Lekarz może widzieć swój własny profil Doctor.
        return $user->isAdmin()
            || ($user->isDoctor() && $user->doctor?->id === $doctor->id);
    }

    /**
     * Determine whether the user can create models.
     * Tylko admin może tworzyć nowych lekarzy (i powiązanych z nimi użytkowników).
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     * Admin może aktualizować każdego lekarza.
     * Lekarz może aktualizować tylko swój własny profil Doctor.
     */
    public function update(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin()
            || ($user->isDoctor() && $user->doctor?->id === $doctor->id);
    }

    /**
     * Determine whether the user can delete the model.
     * Tylko admin może usuwać lekarzy.
     */
    public function delete(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     * Tylko admin.
     */
    public function restore(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Tylko admin.
     */
    public function forceDelete(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin();
    }
}
