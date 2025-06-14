<?php

namespace Database\Seeders;

use App\Models\Procedure;
use App\Models\ProcedureCategory;
use Illuminate\Database\Seeder;

class ProcedureSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pobierz wszystkie istniejące kategorie
        $categories = ProcedureCategory::all();

        // Dla każdej kategorii dodaj 3 zabiegi
        foreach ($categories as $category) {
            // Pobierz zabiegi dla tej kategorii
            $procedures = $this->getProceduresForCategory($category->name);

            // Ogranicz do 3 zabiegów
            $selectedProcedures = array_slice($procedures, 0, 3);

            // Utwórz każdy zabieg
            foreach ($selectedProcedures as $procedure) {
                Procedure::firstOrCreate(
                    ['name' => $procedure['name']],
                    [
                        'description' => $procedure['description'],
                        'base_price' => fake()->randomFloat(2, $procedure['price'][0], $procedure['price'][1]),
                        'procedure_category_id' => $category->id,
                        'recovery_timeline_info' => $this->generateRecoveryTimeline($category->name),
                        'duration_minutes' => fake()->randomElement([30, 60, 90, 120, 150, 180]),                    ]
                );
            }
        }
    }

    /**
     * Pobiera zabiegi dla danej kategorii.
     */
    private function getProceduresForCategory(string $categoryName): array
    {
        $allProcedures = [
            'Zabiegi na twarz' => [
                [
                    'name' => 'Plastyka powiek górnych',
                    'price' => [2500, 5000],
                    'description' => 'Zabieg usunięcia nadmiaru skóry i tkanki tłuszczowej z górnych powiek, poprawiający wygląd oczu i pole widzenia.'
                ],
                [
                    'name' => 'Plastyka powiek dolnych',
                    'price' => [2800, 5500],
                    'description' => 'Procedura minimalizująca worki pod oczami i nadmiar skóry pod dolnymi powiekami.'
                ],
                [
                    'name' => 'Lifting twarzy',
                    'price' => [15000, 25000],
                    'description' => 'Kompleksowy zabieg odmładzający środkową i dolną część twarzy, usuwający głębokie zmarszczki i opadającą skórę.'
                ]
            ],
            'Modelowanie sylwetki' => [
                [
                    'name' => 'Liposukcja brzucha',
                    'price' => [6000, 12000],
                    'description' => 'Usunięcie nagromadzonej tkanki tłuszczowej z obszaru brzucha dla uzyskania smuklejszej sylwetki.'
                ],
                [
                    'name' => 'Abdominoplastyka',
                    'price' => [12000, 20000],
                    'description' => 'Plastyka brzucha polegająca na usunięciu nadmiaru skóry i tkanki tłuszczowej oraz napięciu mięśni.'
                ],
                [
                    'name' => 'Liposukcja ud',
                    'price' => [6000, 14000],
                    'description' => 'Procedura modelująca obrys ud poprzez odsysanie nadmiaru tłuszczu z problematycznych obszarów.'
                ]
            ],
            'Medycyna estetyczna' => [
                [
                    'name' => 'Botoks',
                    'price' => [800, 2500],
                    'description' => 'Iniekcje toksyny botulinowej redukujące zmarszczki mimiczne czoła i okolic oczu.'
                ],
                [
                    'name' => 'Wypełniacze kwasu hialuronowego',
                    'price' => [1000, 3500],
                    'description' => 'Zastrzyki wypełniające zmarszczki, bruzdy i modelujące rysy twarzy.'
                ],
                [
                    'name' => 'Mezoterapia',
                    'price' => [500, 1500],
                    'description' => 'Seria zastrzyków z substancji odżywczych poprawiających kondycję i nawilżenie skóry.'
                ]
            ],
            'Chirurgia piersi' => [
                [
                    'name' => 'Powiększanie piersi implantami',
                    'price' => [12000, 22000],
                    'description' => 'Zabieg zwiększający rozmiar piersi przy użyciu implantów silikonowych lub wypełnionych solą fizjologiczną.'
                ],
                [
                    'name' => 'Redukcja piersi',
                    'price' => [14000, 20000],
                    'description' => 'Procedura zmniejszająca wielkość piersi poprzez usunięcie nadmiaru tkanki i skóry, poprawiająca proporcje ciała.'
                ],
                [
                    'name' => 'Lifting piersi',
                    'price' => [10000, 18000],
                    'description' => 'Zabieg unoszący opadające piersi i przywracający młodzieńczy wygląd bez zmiany rozmiaru.'
                ]
            ],
            'Zabiegi laserowe' => [
                [
                    'name' => 'Laserowe usuwanie blizn',
                    'price' => [400, 1500],
                    'description' => 'Wykorzystanie precyzyjnej wiązki lasera do złuszczania i regeneracji skóry w miejscu blizny.'
                ],
                [
                    'name' => 'Laserowe odmładzanie skóry',
                    'price' => [600, 2000],
                    'description' => 'Zabieg stymulujący produkcję kolagenu i poprawiający strukturę skóry za pomocą światła laserowego.'
                ],
                [
                    'name' => 'Laserowe usuwanie zmian naczyniowych',
                    'price' => [500, 1800],
                    'description' => 'Precyzyjne usuwanie pajączków naczyniowych i innych zmian naczyniowych za pomocą wiązki świetlnej.'
                ]
            ]
        ];

        return $allProcedures[$categoryName] ?? [];
    }

    /**
     * Generuje realistyczne informacje o okresie rekonwalescencji.
     */
    private function generateRecoveryTimeline(string $category): string
    {
        $timelines = [
            'Zabiegi na twarz' => "Dzień 1-3: Odpoczynek, chłodne kompresy, możliwy obrzęk i zasinienie.\n" .
                "Tydzień 1-2: Kontrola lekarska, usunięcie szwów, łagodny obrzęk.\n" .
                "Miesiąc 1-2: Stopniowy powrót do normalnej aktywności, widoczne wstępne efekty.\n" .
                "Miesiąc 3-6: Pełne efekty zabiegu, zanikanie blizn.",

            'Chirurgia piersi' => "Dzień 1-5: Odpoczynek, noszenie specjalnego stanika, umiarkowany dyskomfort.\n" .
                "Tydzień 1-2: Ograniczenie wysiłku fizycznego, kontrola lekarska.\n" .
                "Tydzień 3-6: Stopniowe zmniejszanie obrzęku, powrót do lekkich aktywności.\n" .
                "Miesiąc 2-6: Osiągnięcie finalnego kształtu piersi, możliwy powrót do pełnej aktywności.",

            'Modelowanie sylwetki' => "Dzień 1-7: Odpoczynek, noszenie odzieży uciskowej, umiarkowany dyskomfort.\n" .
                "Tydzień 2-3: Kontrola lekarska, ograniczenie wysiłku fizycznego.\n" .
                "Tydzień 4-6: Zmniejszenie obrzęku, powrót do lekkich aktywności.\n" .
                "Miesiąc 2-4: Widoczne rezultaty, stopniowy powrót do pełnej aktywności.\n" .
                "Miesiąc 6: Ostateczne efekty zabiegu.",

            'Medycyna estetyczna' => "Dzień 1: Możliwe zaczerwienienie i delikatny obrzęk w miejscu zabiegu.\n" .
                "Dzień 2-3: Ustąpienie większości objawów, możliwy powrót do normalnej aktywności.\n" .
                "Tydzień 1: Widoczne wstępne efekty zabiegu.\n" .
                "Miesiąc 1-3: Pełne efekty zabiegu (zależnie od rodzaju procedury).",

            'Zabiegi laserowe' => "Dzień 1-2: Możliwe zaczerwienienie i obrzęk w obszarze zabiegu.\n" .
                "Dzień 3-7: Stopniowe ustępowanie reakcji skórnych, stosowanie kremów ochronnych.\n" .
                "Tydzień 2-3: Widoczna poprawa stanu skóry, możliwość powrotu do normalnej aktywności.\n" .
                "Miesiąc 1-2: Pełne efekty zabiegu, wzmocnienie struktury skóry."
        ];

        return $timelines[$category] ??
            "Dzień 1-3: Odpoczynek, możliwy obrzęk.\n" .
            "Tydzień 1-2: Kontrola lekarska, ewentualne zdjęcie szwów.\n" .
            "Miesiąc 1-3: Widoczne efekty zabiegu.";
    }
}
