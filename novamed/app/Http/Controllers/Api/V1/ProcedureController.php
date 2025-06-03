<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Procedure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ProcedureCategory;
use App\Http\Resources\Api\V1\ProcedureResource; // <<< --- DODAJ IMPORT
use Illuminate\Http\JsonResponse; // Dla JsonResponse
use Illuminate\Http\Resources\Json\AnonymousResourceCollection; // Dla ResourceCollection

class ProcedureController extends Controller
{
    /**
     * Display a listing of procedures.
     * Can be paginated or limited by 'limit' parameter.
     * Can filter by 'popular' parameter (requires custom logic).
     */
    public function index(Request $request): JsonResponse | AnonymousResourceCollection // Zaktualizowany typ zwrotny
    {
        $query = Procedure::with('category');

        if ($request->has('procedure_category_id') && $request->procedure_category_id !== null) {
            $query->where('procedure_category_id', $request->procedure_category_id);
        }

        // Logika dla "popularnych" procedur - musisz ją zdefiniować
        // np. sortowanie po liczbie rezerwacji, specjalne pole, itp.
        if ($request->boolean('popular')) {
            // Przykładowa logika: załóżmy, że masz kolumnę 'view_count' lub 'is_popular'
            // $query->where('is_popular', true); // LUB
            // $query->orderBy('view_count', 'desc'); // LUB
            $query->inRandomOrder(); // Prosty sposób na "losowe" popularne, jeśli nie masz innej logiki
            // UWAGA: 'inRandomOrder()' może być wolne na dużych tabelach.
            // Rozważ dedykowany mechanizm oznaczania popularnych procedur.
        } else {
            // Domyślne sortowanie dla pełnej listy, jeśli nie są to "popularne"
            $query->orderBy('name'); // lub $query->orderBy('created_at', 'desc');
        }


        // Jeśli przekazano parametr 'limit', pobierz tylko określoną liczbę wyników
        if ($request->has('limit')) {
            $limit = (int) $request->input('limit');
            $procedures = $query->take($limit)->get();
            // Dla kolekcji bez paginacji, Resource::collection zwraca tablicę pod kluczem 'data' automatycznie
            return ProcedureResource::collection($procedures);
        } else {
            // W przeciwnym razie, użyj standardowej paginacji
            $perPage = (int) ($request->input('per_page', 9)); // Domyślnie 9
            $procedures = $query->paginate($perPage);
            // Paginowane zasoby również są automatycznie opakowywane poprawnie
            return ProcedureResource::collection($procedures);
        }
    }

    /**
     * Display the specified procedure.
     */
    public function show(string $id): ProcedureResource | JsonResponse // Zaktualizowany typ zwrotny
    {
        $procedure = Procedure::with('category')->find($id);

        if (!$procedure) {
            return response()->json(['message' => 'Procedure not found.'], 404);
        }
        return new ProcedureResource($procedure);
    }

    /**
     * Get distinct procedure categories.
     */
    public function categories(): JsonResponse // Typ zwrotny jest już JsonResponse
    {
        try {
            $categories = ProcedureCategory::select('id', 'name', 'slug')
                ->orderBy('name')
                ->get();
            // Możesz też użyć Resource dla kategorii, jeśli chcesz spójności
            // return ProcedureCategoryResource::collection($categories);
            return response()->json(['data' => $categories]); // Opakuj w 'data' dla spójności z innymi endpointami
        } catch (\Exception $e) {
            Log::error('Błąd podczas pobierania kategorii procedur: ' . $e->getMessage());
            return response()->json(['error' => 'Wystąpił błąd podczas pobierania kategorii'], 500);
        }
    }
}
