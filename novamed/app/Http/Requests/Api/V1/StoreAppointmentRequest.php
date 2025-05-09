<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Appointment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => 'required|exists:doctors,id',
            'procedure_id' => 'required|exists:procedures,id',
            'appointment_datetime' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    $exists = Appointment::where('doctor_id', $this->doctor_id)
                        ->where('appointment_datetime', $value)
                        ->exists();

                    if ($exists) {
                        $fail('Ten termin jest już zajęty.');
                    }
                },
            ],
            'patient_notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'doctor_id.required' => 'Wybór lekarza jest wymagany.',
            'doctor_id.exists' => 'Wybrany lekarz nie istnieje w systemie.',
            'procedure_id.required' => 'Wybór zabiegu jest wymagany.',
            'procedure_id.exists' => 'Wybrany zabieg nie istnieje w systemie.',
            'appointment_datetime.required' => 'Data i godzina wizyty są wymagane.',
            'appointment_datetime.date' => 'Nieprawidłowy format daty.',
            'appointment_datetime.after_or_equal' => 'Data wizyty nie może być wcześniejsza niż dzisiaj.',
            'patient_notes.max' => 'Uwagi nie mogą przekraczać 500 znaków.',
        ];
    }
}
