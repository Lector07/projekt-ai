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
        return [
            'bio' => 'nullable|string|max:2000',
            'specialization' => 'sometimes|required|string|max:255', // Jeśli lekarz może zmieniać
            'procedure_ids' => 'nullable|array',
            'procedure_ids.*' => 'integer|exists:procedures,id',
            // Inne pola, które lekarz może edytować
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
