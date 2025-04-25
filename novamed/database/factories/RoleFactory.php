<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str; // <<< IMPORTUJ Str

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Lepsze jest tworzenie konkretnych ról w seederze, np. Role::create(...)
        // Jeśli używasz fabryki:
        $name = fake()->unique()->jobTitle(); // Przykładowa nazwa
        return [
            'name' => $name,
            'slug' => Str::slug($name), // Generowanie sluga
            // Usunięto 'description'
        ];
    }
}
