<?php

namespace App\Http\Requests\Api\V1\Doctor; // Przestrzeń nazw jest OK

use App\Models\Appointment; // Dodaj import Appointment
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth; // Możesz użyć Auth dla uproszczenia

class UpdateDoctorAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 1. Sprawdź, czy użytkownik jest zalogowany i jest lekarzem
        if (!Auth::check() || !$this->user()->isDoctor()) {
            return false;
        }

        // 2. Pobierz wizytę z trasy (route model binding powinien ją już załadować)
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');

        // 3. Sprawdź, czy wizyta należy do tego lekarza
        // $this->user()->doctor zakłada, że relacja 'doctor' na modelu User zwraca model Doctor
        // a model Doctor ma klucz główny 'id'.
        // Jeśli model User bezpośrednio reprezentuje lekarza i ma doctor_id, użyj $this->user()->id
        if ($appointment && $this->user()->doctor && $this->user()->doctor->id === $appointment->doctor_id) {
            return true;
        }

        // Jeśli powyższe nie jest prawdą, możesz też użyć AppointmentPolicy tutaj,
        // ale często jest to już robione w kontrolerze.
        // return $this->user()->can('updateByDoctor', $appointment); // Zakłada metodę 'updateByDoctor' w AppointmentPolicy

        return false; // Domyślnie odmów, jeśli warunki nie są spełnione
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment'); // Pobierz aktualną wizytę, aby znać jej status

        // Logika dozwolonych zmian statusu może zależeć od obecnego statusu
        $allowedStatuses = [];
        if ($appointment) {
            switch ($appointment->status) {
                case 'scheduled':
                    $allowedStatuses = ['confirmed', 'cancelled_by_clinic'];
                    break;
                case 'confirmed':
                    $allowedStatuses = ['completed', 'no_show', 'cancelled_by_clinic'];
                    // Można by dodać 'rescheduled_by_doctor', jeśli obsługujesz zmianę terminu
                    break;
                // Lekarz raczej nie powinien zmieniać statusów: completed, no_show, cancelled_by_clinic, cancelled_by_patient
            }
        } else {
            // Jeśli z jakiegoś powodu wizyta nie została załadowana (co nie powinno się zdarzyć przy route model binding)
            // Możesz ustawić domyślne, bardziej restrykcyjne reguły lub pozwolić kontrolerowi to obsłużyć.
            // Na razie załóżmy, że wizyta zawsze będzie.
        }


        return [
            'status' => [
                'sometimes', // 'sometimes' oznacza, że pole jest walidowane tylko jeśli jest obecne w żądaniu
                'required',  // Ale jeśli jest obecne, musi mieć wartość
                'string',
                Rule::in($allowedStatuses),
            ],
            // Użyj doctor_notes zamiast admin_notes, jeśli to notatki specyficzne dla lekarza,
            // a admin_notes są dla administratora systemu. Jeśli to to samo pole, nazwa jest OK.
            'doctor_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            // Jeśli lekarz może przekładać wizyty:
            // 'appointment_datetime' => [
            //     'sometimes',
            //     'required',
            //     'date_format:Y-m-d H:i:s',
            //     'after:now',
            //     Rule::unique('appointments')->where(function ($query) use ($appointment) {
            //         return $query->where('doctor_id', $appointment->doctor_id)
            //                      ->where('id', '!=', $appointment->id) // Ignoruj obecną wizytę przy sprawdzaniu unikalności
            //                      ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_clinic']);
            //     }),
            // ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wizyty jest wymagany, jeśli jest wysyłany.',
            'status.in' => 'Wybrano nieprawidłowy lub niedozwolony status dla tej wizyty.',
            'doctor_notes.string' => 'Notatki lekarza muszą być tekstem.',
            'doctor_notes.max' => 'Notatki lekarza nie mogą przekraczać 2000 znaków.',
            // 'appointment_datetime.required' => 'Nowy termin wizyty jest wymagany, jeśli jest wysyłany.',
            // 'appointment_datetime.date_format' => 'Nieprawidłowy format daty i godziny.',
            // 'appointment_datetime.after' => 'Nowy termin wizyty musi być datą przyszłą.',
            // 'appointment_datetime.unique' => 'Wybrany nowy termin jest już zajęty dla tego lekarza.',
        ];
    }
}
