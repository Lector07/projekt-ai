<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserAvatarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() ? true : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
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
