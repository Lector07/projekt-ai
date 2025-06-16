<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $patients = User::where('role', 'patient')->get();
        $doctors = Doctor::with('user')->get();
        $procedures = Procedure::all();

        if ($patients->isEmpty() || $doctors->isEmpty() || $procedures->isEmpty()) {
            $this->command->error('Brak pacjentów, lekarzy lub procedur. Najpierw uruchom odpowiednie seedery.');
            return;
        }

        for ($month = 1; $month <= 12; $month++) {
            $appointmentsInMonth = rand(3, 8);

            for ($i = 0; $i < $appointmentsInMonth; $i++) {
                $date = Carbon::create(2025, $month, rand(1, 28));
                $hour = rand(8, 17);
                $minute = rand(0, 3) * 15;
                $appointmentDateTime = $date->setTime($hour, $minute);
                $status = $date->isPast() ? 'completed' : 'scheduled';

                Appointment::create([
                    'patient_id' => $patients->random()->id,
                    'doctor_id' => $doctors->random()->id,
                    'procedure_id' => $procedures->random()->id,
                    'appointment_datetime' => $appointmentDateTime,
                    'status' => rand(1, 10) > 1 ? $status : 'cancelled',
                    'patient_notes' => fake()->optional(0.3)->sentence(),
                    'admin_notes' => fake()->optional(0.2)->sentence(),
                ]);
            }
        }

        $this->command->info('Utworzono wizyty rozłożone na cały rok.');
    }
}
