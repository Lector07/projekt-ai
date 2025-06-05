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
        $patient = User::where('role', 'patient')->inRandomOrder()->first();

        $doctor = Doctor::inRandomOrder()->first();

        $procedure = Procedure::inRandomOrder()->first();

        $appointmentDate = Carbon::now()->addDays(rand(1, 30))->format('Y-m-d');
        $appointmentHour = rand(9, 16);
        $appointmentMinute = rand(0, 1) * 30;
        $appointmentTime = sprintf('%02d:%02d:00', $appointmentHour, $appointmentMinute);
        $appointmentDateTime = $appointmentDate . ' ' . $appointmentTime;

        return [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'procedure_id' => $procedure->id,
            'appointment_datetime' => $appointmentDateTime,
            'status' => $this->faker->randomElement(['scheduled', 'completed', 'cancelled', 'no_show', 'cancelled_by_clinic']),
            'patient_notes' => fake()->optional(0.3)->sentence(),
            'admin_notes' => fake()->optional(0.2)->sentence(),
        ];
    }
}
