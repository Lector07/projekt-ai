<?php

namespace Database\Factories;

use App\Models\ProcedureCategory;
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
        // Tablice z prawdziwymi zabiegami z chirurgii plastycznej
        $procedures = [
            // Twarz
            ['name' => 'Plastyka powiek górnych', 'price' => [2500, 5000], 'category' => 'Twarz',
                'description' => 'Zabieg usunięcia nadmiaru skóry i tkanki tłuszczowej z górnych powiek, poprawiający wygląd oczu i pole widzenia.'],
            ['name' => 'Plastyka powiek dolnych', 'price' => [2800, 5500], 'category' => 'Twarz',
                'description' => 'Procedura minimalizująca worki pod oczami i nadmiar skóry pod dolnymi powiekami.'],
            ['name' => 'Lifting twarzy', 'price' => [15000, 25000], 'category' => 'Twarz',
                'description' => 'Kompleksowy zabieg odmładzający środkową i dolną część twarzy, usuwający głębokie zmarszczki i opadającą skórę.'],
            ['name' => 'Lifting czoła', 'price' => [8000, 15000], 'category' => 'Twarz',
                'description' => 'Zabieg unoszący opadające brwi i redukujący zmarszczki na czole dla bardziej wypoczętego wyglądu.'],
            ['name' => 'Plastyka nosa', 'price' => [7000, 17000], 'category' => 'Twarz',
                'description' => 'Korekcja kształtu, wielkości lub proporcji nosa, poprawa funkcji oddychania.'],
            ['name' => 'Liposukcja podbródka', 'price' => [4000, 8000], 'category' => 'Twarz',
                'description' => 'Zabieg usuwający nadmiar tkanki tłuszczowej spod podbródka, modelujący linię żuchwy.'],

            // Piersi
            ['name' => 'Powiększanie piersi implantami', 'price' => [12000, 22000], 'category' => 'Piersi',
                'description' => 'Zabieg zwiększający rozmiar piersi przy użyciu implantów silikonowych lub wypełnionych solą fizjologiczną.'],
            ['name' => 'Redukcja piersi', 'price' => [14000, 20000], 'category' => 'Piersi',
                'description' => 'Procedura zmniejszająca wielkość piersi poprzez usunięcie nadmiaru tkanki i skóry, poprawiająca proporcje ciała.'],
            ['name' => 'Lifting piersi', 'price' => [10000, 18000], 'category' => 'Piersi',
                'description' => 'Zabieg unoszący opadające piersi i przywracający młodzieńczy wygląd bez zmiany rozmiaru.'],
            ['name' => 'Rekonstrukcja piersi', 'price' => [15000, 30000], 'category' => 'Piersi',
                'description' => 'Odtworzenie piersi po mastektomii lub innych urazach przy użyciu implantów lub tkanek własnych pacjentki.'],

            // Ciało
            ['name' => 'Liposukcja brzucha', 'price' => [6000, 12000], 'category' => 'Ciało',
                'description' => 'Usunięcie nagromadzonej tkanki tłuszczowej z obszaru brzucha dla uzyskania smuklejszej sylwetki.'],
            ['name' => 'Abdominoplastyka', 'price' => [12000, 20000], 'category' => 'Ciało',
                'description' => 'Plastyka brzucha polegająca na usunięciu nadmiaru skóry i tkanki tłuszczowej oraz napięciu mięśni.'],
            ['name' => 'Liposukcja ud', 'price' => [6000, 14000], 'category' => 'Ciało',
                'description' => 'Procedura modelująca obrys ud poprzez odsysanie nadmiaru tłuszczu z problematycznych obszarów.'],
            ['name' => 'Liposukcja ramion', 'price' => [5000, 10000], 'category' => 'Ciało',
                'description' => 'Zabieg usuwający nadmiar tkanki tłuszczowej z ramion, poprawiający ich kontur.'],
            ['name' => 'Plastyka pośladków', 'price' => [10000, 18000], 'category' => 'Ciało',
                'description' => 'Modelowanie pośladków poprzez lifting, powiększenie implantami lub przeszczep tłuszczu.'],

            // Zabiegi małoinwazyjne
            ['name' => 'Botoks', 'price' => [800, 2500], 'category' => 'Zabiegi małoinwazyjne',
                'description' => 'Iniekcje toksyny botulinowej redukujące zmarszczki mimiczne czoła i okolic oczu.'],
            ['name' => 'Wypełniacze kwasu hialuronowego', 'price' => [1000, 3500], 'category' => 'Zabiegi małoinwazyjne',
                'description' => 'Zastrzyki wypełniające zmarszczki, bruzdy i modelujące rysy twarzy.'],
            ['name' => 'Mezoterapia', 'price' => [500, 1500], 'category' => 'Zabiegi małoinwazyjne',
                'description' => 'Seria zastrzyków z substancji odżywczych poprawiających kondycję i nawilżenie skóry.'],
            ['name' => 'Peelingi chemiczne', 'price' => [400, 1200], 'category' => 'Zabiegi małoinwazyjne',
                'description' => 'Kontrolowane złuszczanie zewnętrznych warstw skóry w celu poprawy jej tekstury i kolorytu.'],
            ['name' => 'Terapia laserowa', 'price' => [600, 3000], 'category' => 'Zabiegi małoinwazyjne',
                'description' => 'Zabiegi wykorzystujące precyzyjne wiązki światła do poprawy szerokiej gamy problemów skórnych.'],
        ];

        $selectedProcedure = fake()->randomElement($procedures);

        // Wyszukaj lub stwórz kategorię pasującą do zabiegu
        $category = ProcedureCategory::firstOrCreate(
            ['name' => $selectedProcedure['category']],
            ['slug' => \Illuminate\Support\Str::slug($selectedProcedure['category'])]
        );

        return [
            'name' => $selectedProcedure['name'],
            'description' => $selectedProcedure['description'],
            'base_price' => fake()->randomFloat(2, $selectedProcedure['price'][0], $selectedProcedure['price'][1]),
            'procedure_category_id' => $category->id,
            'recovery_timeline_info' => $this->generateRecoveryTimeline($selectedProcedure['category']),
        ];
    }

    /**
     * Generuje realistyczne informacje o okresie rekonwalescencji.
     *
     * @param string $category
     * @return string
     */
    private function generateRecoveryTimeline(string $category): string
    {
        $timelines = [
            'Twarz' => "Dzień 1-3: Odpoczynek, chłodne kompresy, możliwy obrzęk i zasinienie.\n" .
                "Tydzień 1-2: Kontrola lekarska, usunięcie szwów, łagodny obrzęk.\n" .
                "Miesiąc 1-2: Stopniowy powrót do normalnej aktywności, widoczne wstępne efekty.\n" .
                "Miesiąc 3-6: Pełne efekty zabiegu, zanikanie blizn.",

            'Piersi' => "Dzień 1-5: Odpoczynek, noszenie specjalnego stanika, umiarkowany dyskomfort.\n" .
                "Tydzień 1-2: Ograniczenie wysiłku fizycznego, kontrola lekarska.\n" .
                "Tydzień 3-6: Stopniowe zmniejszanie obrzęku, powrót do lekkich aktywności.\n" .
                "Miesiąc 2-6: Osiągnięcie finalnego kształtu piersi, możliwy powrót do pełnej aktywności.",

            'Ciało' => "Dzień 1-7: Odpoczynek, noszenie odzieży uciskowej, umiarkowany dyskomfort.\n" .
                "Tydzień 2-3: Kontrola lekarska, ograniczenie wysiłku fizycznego.\n" .
                "Tydzień 4-6: Zmniejszenie obrzęku, powrót do lekkich aktywności.\n" .
                "Miesiąc 2-4: Widoczne rezultaty, stopniowy powrót do pełnej aktywności.\n" .
                "Miesiąc 6: Ostateczne efekty zabiegu.",

            'Zabiegi małoinwazyjne' => "Dzień 1: Możliwe zaczerwienienie i delikatny obrzęk w miejscu zabiegu.\n" .
                "Dzień 2-3: Ustąpienie większości objawów, możliwy powrót do normalnej aktywności.\n" .
                "Tydzień 1: Widoczne wstępne efekty zabiegu.\n" .
                "Miesiąc 1-3: Pełne efekty zabiegu (zależnie od rodzaju procedury)."
        ];

        return $timelines[$category] ??
            "Dzień 1-3: Odpoczynek, możliwy obrzęk.\n" .
            "Tydzień 1-2: Kontrola lekarska, ewentualne zdjęcie szwów.\n" .
            "Miesiąc 1-3: Widoczne efekty zabiegu.";
    }
}
