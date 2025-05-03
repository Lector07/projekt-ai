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
        ];
    }
}
