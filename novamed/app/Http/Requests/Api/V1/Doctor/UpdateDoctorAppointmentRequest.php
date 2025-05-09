<?php

namespace App\Http\Requests\Api\V1\Doctor; // Zwróć uwagę na przestrzeń nazw

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Główna autoryzacja (czy to wizyta tego lekarza) powinna być w Policy
     * wywoływanej w kontrolerze. Tutaj tylko ogólne pozwolenie.
     */
    public function authorize(): bool
    {
        // Zakładamy, że użytkownik jest lekarzem (sprawdzone przez middleware 'auth.doctor')
        // i że kontroler użyje AppointmentPolicy do sprawdzenia, czy może edytować TĘ wizytę.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // Lekarz może mieć ograniczony zakres zmian statusów.
        // Np. może zmienić na 'confirmed', 'completed', ale nie 'booked' czy 'cancelled' przez pacjenta.
        // Admin może zmieniać na wszystkie.
        return [
            'status' => [
                'sometimes', // Jeśli aktualizacja statusu jest opcjonalna
                'required',
                'string',
                // Dostosuj dozwolone statusy dla lekarza
                Rule::in(['confirmed', 'completed', 'rescheduled_by_doctor']), // Przykładowe statusy dla lekarza
            ],
            'admin_notes' => ['sometimes', 'nullable', 'string', 'max:1000'], // Lekarz może dodawać/edytować 'admin_notes'
            // Lekarz prawdopodobnie nie powinien zmieniać: patient_id, doctor_id, procedure_id, appointment_datetime
            // chyba że masz specyficzną logikę np. dla przełożenia wizyty (co wymagałoby 'appointment_datetime').
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wizyty jest wymagany.',
            'status.in' => 'Wybrano nieprawidłowy status wizyty.',
            'admin_notes.max' => 'Notatki nie mogą przekraczać 1000 znaków.',
        ];
    }
}
