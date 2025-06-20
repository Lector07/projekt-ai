<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Procedure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ProcedureCategory;
use App\Http\Resources\Api\V1\ProcedureResource; // <<< --- DODAJ IMPORT
use Illuminate\Http\JsonResponse; // Dla JsonResponse
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProcedureController extends Controller
{

    public function index(Request $request): JsonResponse | AnonymousResourceCollection
    {
        $query = Procedure::with('category');

        if ($request->has('procedure_category_id') && $request->procedure_category_id !== null) {
            $query->where('procedure_category_id', $request->procedure_category_id);
        }


        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', (float)$request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', (float)$request->max_price);
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        if ($request->boolean('popular')) {
            $query->inRandomOrder();
        } else {
            $allowedSortColumns = ['name', 'base_price'];
            if (in_array($sortBy, $allowedSortColumns)) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('name', 'asc');
            }
        }

        if ($request->has('limit')) {
            $limit = (int) $request->input('limit');
            $procedures = $query->take($limit)->get();
            return ProcedureResource::collection($procedures);
        } else {
            $perPage = (int) ($request->input('per_page', 9));
            $procedures = $query->paginate($perPage);
            return ProcedureResource::collection($procedures);
        }
    }

    /**
     * Display the specified procedure.
     */
    public function show(string $id): ProcedureResource | JsonResponse
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
    public function categories(): JsonResponse
    {
        try {
            $categories = ProcedureCategory::select('id', 'name', 'slug')
                ->orderBy('name')
                ->get();
            return response()->json(['data' => $categories]);
        } catch (\Exception $e) {
            Log::error('Błąd podczas pobierania kategorii procedur: ' . $e->getMessage());
            return response()->json(['error' => 'Wystąpił błąd podczas pobierania kategorii'], 500);
        }
    }
}

