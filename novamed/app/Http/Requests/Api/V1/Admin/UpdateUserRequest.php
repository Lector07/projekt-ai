<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class UpdateUserRequest extends FormRequest
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
        $userIdToIgnore = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users')->ignore($userIdToIgnore),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['sometimes', 'required', 'string', Rule::in(['admin', 'patient', 'doctor'])], // <<< Zmieniono walidację roli
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa jest wymagana.',
            'name.string' => 'Nazwa musi być ciągiem znaków.',
            'name.max' => 'Nazwa nie może przekraczać 255 znaków.',
            'email.required' => 'Adres e-mail jest wymagany.',
            'email.string' => 'Adres e-mail musi być ciągiem znaków.',
            'email.lowercase' => 'Adres e-mail musi być małymi literami.',
            'email.email' => 'Adres e-mail musi być poprawnym adresem e-mail.',
            'email.max' => 'Adres e-mail nie może przekraczać 255 znaków.',
            'email.unique' => 'Ten adres e-mail jest już zajęty.',
            'password.confirmed' => 'Hasła nie pasują do siebie.',
            'role.required' => 'Rola jest wymagana.',
            'role.string' => 'Rola musi być ciągiem znaków.',
            'role.in' => 'Nieprawidłowa rola użytkownika. Dozwolone wartości to: admin, patient, doctor.',
        ];
    }
}
