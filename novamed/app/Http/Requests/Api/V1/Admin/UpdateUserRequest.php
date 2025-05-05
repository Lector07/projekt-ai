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
        // Pobierz ID użytkownika bezpośrednio z segmentu trasy
        // Laravel automatycznie dopasuje {user} z definicji trasy apiResource
        $userId = $this->route('user'); // Zwraca obiekt User lub ID w zależności od konfiguracji

        // Upewnijmy się, że mamy ID
        $userIdToIgnore = ($userId instanceof User) ? $userId->id : $userId;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                // Użyj uzyskanego ID w metodzie ignore
                Rule::unique('users')->ignore($userIdToIgnore),
            ],
            'password' => [
                'nullable',
                'confirmed',
                Password::defaults(),
            ],
            'roles' => ['sometimes', 'required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }
}
