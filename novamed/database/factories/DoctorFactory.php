<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'specialization' => fake()->randomElement(['Chirurg Plastyczny', 'Medycyna Estetyczna', 'Dermatolog', 'Fleobolog']),
            'bio' => fake()->sentence(rand(10, 20)),
            'profile_picture_path' => null, // Domyślnie brak zdjęcia
            'price_modifier' => fake()->randomElement([1.00, 1.10, 1.20, 0.95]),
            'user_id' => null,
            // Usunięto 'user_id', 'license_number'
        ];
    }

    public function withUser(): self
    {
        return $this->state(function (array $attributes) {
            $user = User::factory()->create();
            $doctorRole = \App\Models\Role::where('slug', 'doctor')->firstOrFail();
            $user->roles()->attach($doctorRole->id);

            return [
                'user_id' => $user->id,
            ];
        });
    }
}
