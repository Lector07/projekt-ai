<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Procedure; // <<< IMPORTUJ Procedure
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // --- WAŻNE: Pobieranie istniejących rekordów ---
        // Zakłada, że Seeder stworzył już użytkowników z rolą 'patient', lekarzy i procedury.

        // Losowy pacjent (Użytkownik z odpowiednią rolą)
        $patient = User::whereHas('roles', function ($query) {
            $query->where('slug', 'patient'); // Zakładamy, że slug roli pacjenta to 'patient'
        })->inRandomOrder()->first();

        // Losowy lekarz
        $doctor = Doctor::inRandomOrder()->first();

        // Losowa procedura
        $procedure = Procedure::inRandomOrder()->first();

        // --- SPRAWDZENIE ---
        // Jeśli nie można znaleźć potrzebnych danych (np. seeder nie stworzył pacjentów),
        // fabryka nie może stworzyć poprawnej wizyty.
        if (!$patient || !$doctor || !$procedure) {
            // Rzucenie wyjątku jest dobrym pomysłem, aby zatrzymać seedowanie i wskazać problem.
            throw new \RuntimeException('Cannot create Appointment: Missing required Patient, Doctor, or Procedure data. Ensure dependent seeders run first and create data.');
            // Alternatywnie: return []; // Zwróci pustą tablicę, co może spowodować błąd później
        }

        return [
            'patient_id' => $patient->id, // Poprawna nazwa klucza i ID istniejącego pacjenta
            'doctor_id' => $doctor->id,   // ID istniejącego lekarza
            'procedure_id' => $procedure->id, // ID istniejącej procedury
            'appointment_datetime' => fake()->dateTimeBetween('-6 months', '+6 months'), // Poprawna nazwa kolumny
            'status' => fake()->randomElement(['booked', 'confirmed', 'completed', 'cancelled']),
            'patient_notes' => fake()->optional(0.3)->sentence(),
            'admin_notes' => fake()->optional(0.2)->sentence(),
        ];
    }
}
