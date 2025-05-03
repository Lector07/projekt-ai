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
}
