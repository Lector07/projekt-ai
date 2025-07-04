<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autoryzacja odbywa się w kontrolerze
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'string',
                'min:8',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Aktualne hasło jest wymagane.',
            'current_password.current_password' => 'Aktualne hasło jest nieprawidłowe.',
            'password.required' => 'Nowe hasło jest wymagane.',
            'password.confirmed' => 'Hasła się nie zgadzają.',
            'password.min' => 'Hasło musi mieć co najmniej 8 znaków.',
            'password.letters' => 'Hasło musi zawierać litery.',
            'password.mixed_case' => 'Hasło musi zawierać wielkie i małe litery.',
            'password.numbers' => 'Hasło musi zawierać co najmniej jedną cyfrę.',
            'password.symbols' => 'Hasło musi zawierać co najmniej jeden symbol.',
            'password.uncompromised' => 'Podane hasło zostało ujawnione w wycieku danych. Wybierz inne hasło.',
        ];
    }
}
