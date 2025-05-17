<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreProcedureRequest;
use App\Http\Requests\Api\V1\Admin\UpdateProcedureRequest;
use App\Http\Resources\Api\V1\ProcedureResource;
use App\Http\Resources\Api\V1\ProcedureCategoryResource;
use App\Models\Procedure;
use App\Models\ProcedureCategory; // Poprawiono z Category na ProcedureCategory
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AdminProcedureController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Procedure::class);

        $query = Procedure::query()->with('category');

        // Wyszukiwanie po nazwie
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        // Filtrowanie po kategorii
        if ($request->filled('category_id')) {
            $query->where('procedure_category_id', $request->category_id); // Zmieniono z category_id
        }

        $perPage = $request->input('per_page', 12);
        $procedures = $query->orderBy('name')->paginate($perPage);

        return ProcedureResource::collection($procedures);
    }

    /**
     * Get all categories.
     */
    public function categories(): JsonResponse
    {
        try {
            $this->authorize('viewAny', Procedure::class);

            $categories = ProcedureCategory::orderBy('name')->get(); // Zmieniono model

            // Zwracamy kolekcję zasobów
            return response()->json([
                'data' => ProcedureCategoryResource::collection($categories)
            ]);

        } catch (\Exception $e) {
            Log::error('Błąd podczas pobierania kategorii: ' . $e->getMessage());
            return response()->json(['message' => 'Wystąpił błąd podczas pobierania kategorii'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProcedureRequest $request): JsonResponse
    {
        $this->authorize('create', Procedure::class);
        $validated = $request->validated();
        $procedure = Procedure::create($validated);

        return (new ProcedureResource($procedure->load('category')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Procedure $procedure): ProcedureResource
    {
        $this->authorize('view', $procedure);
        return new ProcedureResource($procedure->load('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProcedureRequest $request, Procedure $procedure): ProcedureResource
    {
        $this->authorize('update', $procedure);
        $validated = $request->validated();
        $procedure->update($validated);

        return new ProcedureResource($procedure->fresh()->load('category'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Procedure $procedure): Response
    {
        $this->authorize('delete', $procedure);
        $procedure->delete();
        return response()->noContent();
    }
}
