<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Zakładamy, że trasa jest chroniona przez auth:sanctum,
        // więc użytkownik jest zalogowany i może aktualizować swój avatar.
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // max 2MB
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Plik zdjęcia jest wymagany.',
            'avatar.image' => 'Przesłany plik musi być obrazem.',
            'avatar.mimes' => 'Zdjęcie musi być w formacie: jpg, jpeg, png, webp.',
            'avatar.max' => 'Rozmiar zdjęcia nie może przekraczać 2MB.',
        ];
    }
}
