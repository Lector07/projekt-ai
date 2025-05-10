<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Procedure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ProcedureCategory;

class ProcedureController extends Controller
{
    /**
     * Display a listing of procedures.
     */
    public function index(Request $request)
    {
        $query = Procedure::with('category');

        // Filtrowanie po ID kategorii
        if ($request->has('procedure_category_id') && $request->procedure_category_id !== null) {
            $query->where('procedure_category_id', $request->procedure_category_id);
        }

        $procedures = $query->paginate($request->per_page ?? 10);
        return response()->json($procedures);
    }

    /**
     * Display the specified procedure.
     */
    public function show(string $id)
    {
        $procedure = Procedure::with('category')->findOrFail($id);
        return response()->json($procedure);
    }

    /**
     * Get distinct procedure categories.
     */
    public function categories()
    {
        try {
            $categories = ProcedureCategory::select('id', 'name', 'slug')
                ->orderBy('name')
                ->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Błąd podczas pobierania kategorii: ' . $e->getMessage());
            return response()->json(['error' => 'Wystąpił błąd podczas pobierania kategorii'], 500);
        }
    }
}
