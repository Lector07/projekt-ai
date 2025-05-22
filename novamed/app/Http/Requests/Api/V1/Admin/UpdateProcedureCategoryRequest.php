<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcedureCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:procedure_categories,name,' . $this->procedureCategory->id,
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa kategorii jest wymagana.',
            'name.string' => 'Nazwa kategorii musi być ciągiem znaków.',
            'name.max' => 'Nazwa kategorii nie może przekraczać 255 znaków.',
            'name.unique' => 'Kategoria o tej nazwie już istnieje.',
        ];
    }
}
