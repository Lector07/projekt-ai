<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcedureRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'base_price' => 'required|numeric|min:0',
            'procedure_category_id' => 'required|exists:procedure_categories,id',
            'recovery_timeline_info' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa procedury jest wymagana.',
            'name.string' => 'Nazwa procedury musi być tekstem.',
            'name.max' => 'Nazwa procedury nie może przekraczać 255 znaków.',

            'description.string' => 'Opis procedury musi być tekstem.',
            'description.max' => 'Opis procedury nie może przekraczać 1000 znaków.',

            'base_price.required' => 'Cena podstawowa jest wymagana.',
            'base_price.numeric' => 'Cena podstawowa musi być wartością liczbową.',
            'base_price.min' => 'Cena podstawowa nie może być mniejsza niż 0.',

            'procedure_category_id.required' => 'Kategoria procedury jest wymagana.',
            'procedure_category_id.exists' => 'Wybrana kategoria procedury nie istnieje.',

            'recovery_timeline_info.string' => 'Informacja o czasie rekonwalescencji musi być tekstem.',
            'recovery_timeline_info.max' => 'Informacja o czasie rekonwalescencji nie może przekraczać 1000 znaków.',
        ];
    }
}
