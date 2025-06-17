<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UpdateProcedureCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Pobierz obiekt kategorii z trasy
        $procedureCategory = $this->route('procedure_category');

        // Pobierz ID kategorii
        $categoryId = $procedureCategory ? $procedureCategory->id : null;

        Log::debug('Aktualizacja kategorii o ID: ' . $categoryId);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('procedure_categories', 'name')->ignore($categoryId)
            ],
            'slug' => [
                'nullable',
                'string',
                'max:1000'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa kategorii jest wymagana.',
            'name.string' => 'Nazwa kategorii musi być ciągiem znaków.',
            'name.max' => 'Nazwa kategorii nie może przekraczać 255 znaków.',
            'name.unique' => 'Kategoria o tej nazwie już istnieje.',
            'description.string' => 'Opis kategorii musi być ciągiem znaków.',
            'description.max' => 'Opis kategorii nie może przekraczać 1000 znaków.'
        ];
    }
}
