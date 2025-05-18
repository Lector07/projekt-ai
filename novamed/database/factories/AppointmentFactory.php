<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        // Znajdź losowego użytkownika z rolą 'patient'
        $patient = User::where('role', 'patient')->inRandomOrder()->first();

        // Znajdź losowego lekarza
        $doctor = Doctor::inRandomOrder()->first();

        // Znajdź losową procedurę
        $procedure = Procedure::inRandomOrder()->first();

        // Ustaw losową datę w zakresie od teraz do +30 dni
        $appointmentDate = Carbon::now()->addDays(rand(1, 30))->format('Y-m-d');
        // Losowa godzina między 8:00 a 16:00 z krokiem co 30 minut
        $appointmentHour = rand(8, 16);
        $appointmentMinute = rand(0, 1) * 30;
        $appointmentTime = sprintf('%02d:%02d:00', $appointmentHour, $appointmentMinute);
        $appointmentDateTime = $appointmentDate . ' ' . $appointmentTime;

        return [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'procedure_id' => $procedure->id,
            'appointment_datetime' => $appointmentDateTime,
            'status' => $this->faker->randomElement(['scheduled', 'completed', 'cancelled', 'no_show']),
            'patient_notes' => fake()->optional(0.3)->sentence(),
            'admin_notes' => fake()->optional(0.2)->sentence(),
        ];
    }
}
