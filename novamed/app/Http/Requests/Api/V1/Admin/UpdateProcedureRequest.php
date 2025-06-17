<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcedureRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'base_price' => 'sometimes|required|numeric|min:0',
            'procedure_category_id' => 'sometimes|required|exists:procedure_categories,id',
            'recovery_timeline_info' => 'sometimes|nullable|string|max:1000',
            'duration' => 'nullable|integer|min:5',
            ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa zabiegu jest wymagana.',
            'name.string' => 'Nazwa zabiegu musi być ciągiem znaków.',
            'name.max' => 'Nazwa zabiegu nie może przekraczać 255 znaków.',
            'description.string' => 'Opis zabiegu musi być ciągiem znaków.',
            'description.max' => 'Opis zabiegu nie może przekraczać 1000 znaków.',
            'base_price.required' => 'Cena podstawowa jest wymagana.',
            'base_price.numeric' => 'Cena podstawowa musi być liczbą.',
            'base_price.min' => 'Cena podstawowa musi być większa lub równa 0.',
            'procedure_category_id.required' => 'Kategoria zabiegu jest wymagana.',
            'procedure_category_id.exists' => 'Wybrana kategoria zabiegu nie istnieje.',
            'recovery_timeline_info.string' => 'Informacje o czasie rekonwalescencji muszą być ciągiem znaków.',
            'recovery_timeline_info.max' => 'Informacje o czasie rekonwalescencji nie mogą przekraczać 1000 znaków.',
            'duration.integer' => 'Czas trwania musi być liczbą całkowitą.',
            'duration.min' => 'Czas trwania musi wynosić co najmniej 5 minut.',
        ];
    }
}
