<?php

namespace Database\Factories;

use App\Models\ProcedureCategory; // <<< IMPORTUJ ProcedureCategory
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Procedure>
 */
class ProcedureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Pobierz ID istniejącej kategorii - kategorie MUSZĄ być stworzone WCZEŚNIEJ w seederze!
        $category = ProcedureCategory::inRandomOrder()->first();

        return [
            'name' => fake()->unique()->words(rand(2, 4), true),
            'description' => fake()->paragraph(rand(3, 6)),
            'base_price' => fake()->randomFloat(2, 500, 15000),
            // Przypisz ID istniejącej kategorii (lub null, jeśli kategorie mogą nie istnieć)
            'procedure_category_id' => $category?->id, // Użyj "?->" na wypadek, gdyby nie było kategorii
            'recovery_timeline_info' => "Dzień 1-" . ($d = rand(2,5)) . ": Odpoczynek, możliwy obrzęk.\nTydzień 1-" . rand(2,3) . ": Kontrola, ew. zdjęcie szwów.\nMiesiąc " . $d . "-" . ($d + rand(1, 3)) . ": Widoczne efekty.",
        ];
    }
}
