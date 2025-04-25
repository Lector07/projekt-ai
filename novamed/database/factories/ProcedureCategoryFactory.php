<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str; // <<< IMPORTUJ Str

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProcedureCategory>
 */
class ProcedureCategoryFactory extends Factory
{
    /**
     * Określ nazwę tabeli modelu (jeśli jest niestandardowa).
     * W modelu zdefiniowaliśmy protected $table, więc to jest opcjonalne.
     */
    // protected $model = \App\Models\ProcedureCategory::class;


    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Zabiegi na twarz', 'Modelowanie sylwetki', 'Medycyna estetyczna', 'Chirurgia piersi', 'Zabiegi laserowe']);
        return [
            'name' => $name,
            'slug' => Str::slug($name), // Generowanie sluga
            // Usunięto 'description'
        ];
    }
}
