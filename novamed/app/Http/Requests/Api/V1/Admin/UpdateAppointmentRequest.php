<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(['booked', 'confirmed', 'completed', 'cancelled_by_clinic', 'cancelled_by_patient', 'cancelled', 'no_show'])],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }


    public function messages(): array
    {
        return [
            'status.required' => 'The status field is required.',
            'status.string' => 'The status must be a string.',
            'status.in' => 'The selected status is invalid.',
            'admin_notes.string' => 'The admin notes must be a string.',
            'admin_notes.max' => 'The admin notes may not be greater than 1000 characters.',
        ];

    }
}
