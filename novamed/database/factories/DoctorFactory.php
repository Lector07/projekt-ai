<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'specialization' => fake()->randomElement(['Chirurg Plastyczny', 'Medycyna Estetyczna', 'Dermatolog', 'Fleobolog']),
            'bio' => fake()->sentence(rand(10, 20)),
            'profile_picture_path' => null,
            'price_modifier' => fake()->randomElement([1.00, 1.10, 1.20, 0.95]),
            'user_id' => null, // Domyślnie null
        ];
    }

    /**
     * Indicate that the doctor should be associated with a new user with the 'doctor' role.
     */
    public function withUser(): self
    {
        return $this->state(function (array $attributes) {
            // Stwórz usera z rolą 'doctor'
            $user = User::factory()->create([
                'role' => 'doctor' // <<< Ustaw rolę bezpośrednio
            ]);
            // Nie potrzeba Role::where ani attach

            return [
                'user_id' => $user->id,
            ];
        });
    }
}
