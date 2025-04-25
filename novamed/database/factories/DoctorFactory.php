<?php

namespace Database\Factories;

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
            // Usunięto 'user_id', 'license_number'
        ];
    }
}
