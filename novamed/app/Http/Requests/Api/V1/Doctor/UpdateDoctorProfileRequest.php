<?php

namespace App\Http\Requests\Api\V1\Doctor; // Zwróć uwagę na przestrzeń nazw

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User; // Jeśli np. specjalizacja miałaby być unikalna wśród lekarzy powiązanych z userami

class UpdateDoctorProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Zakładamy, że middleware 'auth.doctor' lub polityka już sprawdziły,
     * czy użytkownik jest lekarzem i czy próbuje edytować swój profil.
     * Jeśli polityka DoctorPolicy@update jest dobrze zaimplementowana w kontrolerze,
     * to authorize() tutaj może zawsze zwracać true.
     */
    public function authorize(): bool
    {
        // Jeśli chcesz dodatkową warstwę, możesz sprawdzić:
        // return $this->user() && $this->user()->isDoctor() && $this->user()->doctor?->id === $this->route('doctor_profile_to_update_if_passed_by_route_param');
        // Ale zazwyczaj wystarczy:
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // Lekarz może aktualizować tylko niektóre pola swojego profilu Doctor.
        // 'first_name' i 'last_name' są zazwyczaj w modelu User i edytowane przez UserProfileController.
        // 'user_id' jest niezmienne po utworzeniu.
        return [
            'specialization' => ['sometimes', 'required', 'string', 'max:255'], // 'sometimes' jeśli aktualizacja jest opcjonalna
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'price_modifier' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9.99'], // Max np. 999%
            // Możesz dodać tu walidację dla 'profile_picture', gdy będziesz implementować upload
            // 'profile_picture' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            // Walidacja dla ewentualnego grafiku dostępności (bardziej złożona)
            // 'availability.monday' => ['nullable', 'array'],
            // 'availability.monday.*' => ['string', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'], // np. 09:00-17:00
            // ... analogicznie dla innych dni ...
        ];
    }

    public function messages(): array
    {
        return [
            'specialization.required' => 'Specjalizacja jest wymagana.',
            'bio.max' => 'Opis bio nie może przekraczać 2000 znaków.',
            'price_modifier.numeric' => 'Modyfikator ceny musi być liczbą.',
            'price_modifier.min' => 'Modyfikator ceny nie może być ujemny.',
            // 'availability.monday.*.regex' => 'Nieprawidłowy format czasu dla poniedziałku (oczekiwano GG:MM-GG:MM).',
        ];
    }
}
