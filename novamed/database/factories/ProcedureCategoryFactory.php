<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProcedureCategory>
 */
class ProcedureCategoryFactory extends Factory
{

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Zabiegi na twarz', 'Modelowanie sylwetki', 'Medycyna estetyczna', 'Chirurgia piersi', 'Zabiegi laserowe']);

        $descriptions = [
            'Zabiegi na twarz' => 'Kompleksowe zabiegi odmładzające i regenerujące skórę twarzy. Oferujemy profesjonalne liftingi, peelingi oraz zabiegi rozświetlające, które poprawiają wygląd i kondycję skóry.',
            'Modelowanie sylwetki' => 'Nieinwazyjne i skuteczne metody modelowania sylwetki. Zabiegi redukujące tkankę tłuszczową, ujędrniające i poprawiające kształt ciała.',
            'Medycyna estetyczna' => 'Nowoczesne zabiegi z zakresu medycyny estetycznej wykonywane przez doświadczonych specjalistów. Terapie anti-aging, wypełniacze oraz mezoterapia.',
            'Chirurgia piersi' => 'Specjalistyczne zabiegi chirurgiczne piersi wykonywane przez certyfikowanych lekarzy. Powiększanie, modelowanie i rekonstrukcja piersi z wykorzystaniem najnowszych technik.',
            'Zabiegi laserowe' => 'Zaawansowane terapie laserowe dostosowane do różnych potrzeb skóry. Efektywne usuwanie przebarwień, zmian naczyniowych, blizn oraz zbędnego owłosienia.'
        ];

        return [
            'name' => $name,
            'slug' => $descriptions[$name] ?? fake()->paragraph(2)
        ];
    }
}
