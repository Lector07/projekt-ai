<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;


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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
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
                    ->uncompromised()
            ],
            'role' => ['required', 'string', Rule::in(['admin', 'patient', 'doctor'])],
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
            'password.mixed_case' => 'Hasło musi zawierać wielkie i małe litery',
            'password.letters' => 'Hasło musi zawierać litery',
            'password.min' => 'Hasło musi mieć co najmniej 8 znaków',
            'password.symbols' => 'Hasło musi zawierać symbole',
            'password.uncompromised' => 'Hasło nie może być powszechnie używane lub łatwe do odgadnięcia',
            'role.required' => 'Rola jest wymagana',
            'role.in' => 'Nieprawidłowa rola użytkownika'
        ];
    }
}
