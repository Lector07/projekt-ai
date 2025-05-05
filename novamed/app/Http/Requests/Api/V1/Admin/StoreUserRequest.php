<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

// TODO: I. Polityki Autoryzacji (Policies)
// TODO: Wygenerować UserPolicy, DoctorPolicy, ProcedurePolicy (jeśli nie istnieją)
// TODO: Zaimplementować metody viewAny, view, create, update, delete w UserPolicy - główna logika: return $user->hasRole('admin');
// TODO: Zaimplementować metody viewAny, view, create, update, delete w DoctorPolicy - główna logika: return $user->hasRole('admin');
// TODO: Zaimplementować metody viewAny, view, create, update, delete w ProcedurePolicy - główna logika: return $user->hasRole('admin');
// TODO: Zweryfikować/poprawić metody view, update, delete w AppointmentPolicy - dodać warunek || $user->hasRole('admin')
// TODO: Zarejestrować polityki w bootstrap/app.php lub AuthServiceProvider

// TODO: II. Form Requests dla Admina (już zaimplementowane)
// TODO: Upewnić się, że wszystkie requesty mają authorize() = true

// TODO: III. Kontrolery Administracyjne
// TODO: AdminUserController: zaimplementować metody index, store, show, update, destroy
// TODO: AdminDoctorController: zaimplementować metody index, store, show, update, destroy
// TODO: AdminProcedureController: zaimplementować metody index, store, show, update, destroy
// TODO: AdminAppointmentController: zaimplementować metody index, show, update, destroy
// TODO: AdminDashboardController: zaimplementować metodę index (statystyki)

// TODO: IV. API Resources dla Admina (opcjonalnie)
// TODO: Rozważyć wygenerowanie AdminUserResource, AdminDoctorResource itd.

// TODO: V. Testowanie Endpointów Admina
// TODO: Utworzyć pliki testów: AdminUsersTest, AdminDoctorsTest, AdminProceduresTest, AdminAppointmentsTest
// TODO: Napisać testy CRUD dla każdego zasobu
// TODO: Napisać testy autoryzacji

// TODO: VI. Definicje tras API dla panelu admina
// TODO: Zdefiniować trasy API dla zasobów administracyjnych w routes/api.php

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }
}
