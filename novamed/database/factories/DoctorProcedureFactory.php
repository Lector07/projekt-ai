<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\DoctorProcedure;
use App\Models\Procedure;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorProcedureFactory extends Factory
{
    protected $model = DoctorProcedure::class;

    public function definition(): array
    {
        $doctor = Doctor::inRandomOrder()->first() ?? Doctor::factory()->create();
        $procedure = Procedure::inRandomOrder()->first() ?? Procedure::factory()->create();

        return [
            'doctor_id' => $doctor->id,
            'procedure_id' => $procedure->id,
        ];
    }

    public function forDoctor(Doctor $doctor): static
    {
        return $this->state(fn (array $attributes) => [
            'doctor_id' => $doctor->id,
        ]);
    }

    public function forProcedure(Procedure $procedure): static
    {
        return $this->state(fn (array $attributes) => [
            'procedure_id' => $procedure->id,
        ]);
    }
}
