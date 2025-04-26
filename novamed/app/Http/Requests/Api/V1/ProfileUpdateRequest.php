<?php

namespace App\Http\Requests\Api\V1; // <<< Upewnij się, że namespace jest poprawny

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Zakładamy, że każdy zalogowany użytkownik może aktualizować SWÓJ profil.
     * Middleware 'auth:sanctum' na trasie zapewni, że użytkownik jest zalogowany.
     */
    public function authorize(): bool
    {
        return true; // Pozwól zalogowanemu użytkownikowi na wysłanie żądania
    }

    /**
     * Get the validation rules that apply to the request.
     * Te reguły są poprawne.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                // Poprawna reguła unikalności ignorująca obecnego użytkownika
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Możesz tu dodać walidację dla innych pól profilu, jeśli pozwolisz je edytować
        ];
    }

    // Metoda update() została USUNIĘTA stąd!
}
