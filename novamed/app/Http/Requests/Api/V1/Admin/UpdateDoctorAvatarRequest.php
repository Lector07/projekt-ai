<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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
