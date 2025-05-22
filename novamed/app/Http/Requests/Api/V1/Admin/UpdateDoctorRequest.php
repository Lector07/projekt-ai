<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
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
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'specialization' => 'sometimes|required|string|max:255',
            'bio' => 'sometimes|nullable|string',
            'price_modifier' => 'sometimes|nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Imię jest wymagane.',
            'first_name.string' => 'Imię musi być ciągiem znaków.',
            'first_name.max' => 'Imię nie może przekraczać 255 znaków.',
            'last_name.required' => 'Nazwisko jest wymagane.',
            'last_name.string' => 'Nazwisko musi być ciągiem znaków.',
            'last_name.max' => 'Nazwisko nie może przekraczać 255 znaków.',
            'specialization.required' => 'Specjalizacja jest wymagana.',
            'specialization.string' => 'Specjalizacja musi być ciągiem znaków.',
            'specialization.max' => 'Specjalizacja nie może przekraczać 255 znaków.',
            'bio.string' => 'Opis musi być ciągiem znaków.',
            'price_modifier.numeric' => 'Modyfikator ceny musi być liczbą.',
            'price_modifier.min' => 'Modyfikator ceny musi być większy lub równy 0.',
        ];

    }
}
