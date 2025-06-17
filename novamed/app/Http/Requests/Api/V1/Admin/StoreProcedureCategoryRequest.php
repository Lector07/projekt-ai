<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcedureCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:procedure_categories',
            'slug' => 'nullable|string|max:1000',
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
            'name.required' => 'Nazwa kategorii procedury jest wymagana.',
            'name.string' => 'Nazwa kategorii procedury musi być tekstem.',
            'name.max' => 'Nazwa kategorii procedury nie może być dłuższa niż 255 znaków.',
            'name.unique' => 'Kategoria procedury o takiej nazwie już istnieje.',
            'description.string' => 'Opis kategorii musi być tekstem.',
            'description.max' => 'Opis kategorii nie może przekraczać 1000 znaków.',
        ];
    }
}
