<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'password' => ['required', 'string', Password::defaults()],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */

    public function messages(): array
    {
        return [

            'name.required' => 'Imię jest wymagane',
            'email.required' => 'Email jest wymagany',
            'email.email' => 'Email musi być poprawnym adresem email',
            'email.unique' => 'Email już istnieje',
            'password.required' => 'Hasło jest wymagane',
            'password.defaults' => 'Hasło musi mieć co najmniej 8 znaków, zawierać małe i wielkie litery oraz cyfry',
        ];
    }
}
