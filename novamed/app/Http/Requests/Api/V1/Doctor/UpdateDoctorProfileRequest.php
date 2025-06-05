<?php

namespace App\Http\Requests\Api\V1\Doctor; // Zwróć uwagę na przestrzeń nazw

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User; // Jeśli np. specjalizacja miałaby być unikalna wśród lekarzy powiązanych z userami

class UpdateDoctorProfileRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'bio' => 'nullable|string|max:2000',
            'specialization' => 'sometimes|required|string|max:255',
            'procedure_ids' => 'nullable|array',
            'procedure_ids.*' => 'integer|exists:procedures,id',
        ];
    }

    public function messages(): array
    {
        return [
            'specialization.required' => 'Specjalizacja jest wymagana.',
            'bio.max' => 'Opis bio nie może przekraczać 2000 znaków.',
            'price_modifier.numeric' => 'Modyfikator ceny musi być liczbą.',
            'price_modifier.min' => 'Modyfikator ceny nie może być ujemny.',
        ];
    }
}
