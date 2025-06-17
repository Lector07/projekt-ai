<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Imię jest wymagane',
            'email.required' => 'Adres email jest wymagany',
            'email.email' => 'Podaj prawidłowy adres email',
            'email.unique' => 'Ten adres email jest już zajęty',
            'password.required' => 'Hasło jest wymagane',
            'password.confirmed' => 'Hasła nie są identyczne',
            'password_confirmation.same' => 'Hasła muszą być identyczne',
            'password_confirmation.required' => 'Potwierdzenie hasła jest wymagane'
        ];
    }
}
