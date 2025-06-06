<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
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
            'patient_id' => ['required', 'exists:users,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'procedure_id' => ['required', 'exists:procedures,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', 'in:scheduled,confirmed,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'patient_id.required' => 'Pole pacjent jest wymagane.',
            'patient_id.exists' => 'Wybrany pacjent nie istnieje.',

            'doctor_id.required' => 'Pole lekarz jest wymagane.',
            'doctor_id.exists' => 'Wybrany lekarz nie istnieje.',

            'procedure_id.required' => 'Pole procedura jest wymagane.',
            'procedure_id.exists' => 'Wybrana procedura nie istnieje.',

            'appointment_date.required' => 'Data wizyty jest wymagana.',
            'appointment_date.date' => 'Data wizyty musi być prawidłową datą.',
            'appointment_date.after_or_equal' => 'Data wizyty musi być dzisiejsza lub późniejsza.',

            'start_time.required' => 'Godzina rozpoczęcia jest wymagana.',
            'start_time.date_format' => 'Godzina rozpoczęcia musi być w formacie HH:MM.',

            'end_time.required' => 'Godzina zakończenia jest wymagana.',
            'end_time.date_format' => 'Godzina zakończenia musi być w formacie HH:MM.',
            'end_time.after' => 'Godzina zakończenia musi być późniejsza niż godzina rozpoczęcia.',

            'status.required' => 'Status wizyty jest wymagany.',
            'status.in' => 'Wybrany status jest nieprawidłowy.',

            'notes.string' => 'Notatki muszą być tekstem.',
            'notes.max' => 'Notatki nie mogą przekraczać 1000 znaków.',
        ];
    }
}
