<?php

namespace App\Http\Requests\Api\V1\Doctor;

use App\Models\Appointment; // Dodaj import Appointment
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateDoctorAppointmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        if (!Auth::check() || !$this->user()->isDoctor()) {
            return false;
        }

        $appointment = $this->route('appointment');

        if ($appointment && $this->user()->doctor && $this->user()->doctor->id === $appointment->doctor_id) {
            return true;
        }

        return false;
    }


    public function rules(): array
    {
        $appointment = $this->route('appointment');

        $allowedStatuses = [];
        if ($appointment) {
            switch ($appointment->status) {
                case 'scheduled':
                    $allowedStatuses = ['confirmed', 'cancelled_by_clinic'];
                    break;
                case 'confirmed':
                    $allowedStatuses = ['completed', 'no_show', 'cancelled_by_clinic'];
                    break;
            }
        } else {
            $allowedStatuses = ['confirmed', 'cancelled_by_clinic'];
        }


        return [
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in($allowedStatuses),
            ],
            'doctor_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            //TODO: Przekładanie wizyt ale to na 5
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wizyty jest wymagany, jeśli jest wysyłany.',
            'status.in' => 'Wybrano nieprawidłowy lub niedozwolony status dla tej wizyty.',
            'doctor_notes.string' => 'Notatki lekarza muszą być tekstem.',
            'doctor_notes.max' => 'Notatki lekarza nie mogą przekraczać 2000 znaków.',
        ];
    }
}
