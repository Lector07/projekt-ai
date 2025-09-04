<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReportController extends Controller
{
    public function generateDynamicReport(Request $request)
    {
        $reportConfig = [
            'title' => 'Raport Wydatków Budżetowych',
            'companyInfo' => [
                'name' => 'Klinika Chirurgii Plastycznej NOVAMED',
                'address' => 'ul. Medyczna 1',
                'postalCode' => '00-001',
                'city' => 'Warszawa',
                'taxId' => '123-456-78-90'
            ],
            'columns' => [
                ['field' => 'classificationSymbol', 'header' => 'Klasyfikacja', 'width' => 150],
                ['field' => 'classificationName', 'header' => 'Nazwa', 'width' => -1],
                ['field' => 'budgetAmount', 'header' => 'Budżet', 'format' => '#,##0.00', 'groupCalculation' => 'SUM'],
                ['field' => 'planAmount', 'header' => 'Plan po zmianach', 'format' => '#,##0.00', 'groupCalculation' => 'SUM'],
                ['field' => 'reportRealization', 'header' => 'Realizacja', 'format' => '#,##0.00', 'groupCalculation' => 'SUM'],
                ['field' => 'unitSymbol', 'header' => 'Jedn.', 'width' => 50],
            ],
            'groups' => [
                ['field' => 'sectionSegment', 'label' => '"Dział: " + $F{sectionSegment}', 'showFooter' => true],
                ['field' => 'chapterSegment', 'label' => '"Rozdział: " + $F{chapterSegment}', 'showFooter' => true],
            ],
        ];


        $jsonData = file_get_contents(storage_path('app/data/plan-budzetu-wydatki.json'));

        $payload = [
            'config' => $reportConfig,
            'jsonData' => $jsonData
        ];

        $response = Http::withBody(json_encode($payload), 'application/json')
            ->post('http://localhost:8080/api/generate-dynamic-report');

        if ($response->successful()) {
            return response($response->body(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $response->header('Content-Disposition'),
            ]);
        }

        return response()->json(['error' => 'Błąd generowania raportu', 'details' => $response->body()], 500);
    }
}
