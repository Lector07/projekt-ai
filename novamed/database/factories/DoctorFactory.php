<?php

namespace Database\Factories;

use App\Models\Doctor; // Dodaj import Doctor
use App\Models\User;
// use App\Models\Role; // Niepotrzebne, jeśli rola jest stringiem w User
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $userId = User::factory()->create(['role' => 'doctor'])->id;


        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'specialization' => fake()->randomElement(['Chirurg Plastyczny', 'Medycyna Estetyczna', 'Dermatolog', 'Fleobolog']),
            'bio' => fake()->sentence(rand(10, 20)),
            'profile_picture_path' => null,
            'user_id' => $userId,
        ];
    }

    /**
     * Indicate that the doctor should be associated with a specific user.
     */
    public function forUser(User $user): self
    {
        return $this->state(function (array $attributes) use ($user) {
            $nameParts = explode(' ', $user->name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            return [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'user_id' => $user->id,
            ];
        });
    }
}
