<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProcedureCategoryResource;
use App\Models\ProcedureCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Requests\Api\V1\Admin\StoreProcedureCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateProcedureCategoryRequest;

class AdminProcedureCategoryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Wyświetl listę wszystkich kategorii procedur.
     */
    public function index(): JsonResponse
    {
        try {
            $this->authorize('viewAny', ProcedureCategory::class);

            $categories = ProcedureCategory::orderBy('id', 'asc')->get();
            return response()->json([
                'data' => ProcedureCategoryResource::collection($categories)
            ]);

        } catch (\Exception $e) {
            Log::error('Błąd podczas pobierania kategorii procedur: ' . $e->getMessage());
            return response()->json(['message' => 'Wystąpił błąd podczas pobierania kategorii'], 500);
        }
    }

    /**
     * Zapisz nowo utworzoną kategorię w bazie danych.
     */
    public function store(StoreProcedureCategoryRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', ProcedureCategory::class);
            $validated = $request->validated();

            $validated['slug'] = Str::slug($validated['name']);

            $category = ProcedureCategory::create($validated);

            return (new ProcedureCategoryResource($category))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Błąd podczas tworzenia kategorii: ' . $e->getMessage());

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Błąd walidacji danych',
                    'errors' => $e->errors()
                ], 422);
            }

            return response()->json(['message' => 'Wystąpił błąd podczas tworzenia kategorii'], 500);
        }
    }

    /**
     * Wyświetl szczegóły konkretnej kategorii.
     */
    public function show(ProcedureCategory $procedureCategory): JsonResponse
    {
        try {
            $this->authorize('view', $procedureCategory);

            return response()->json([
                'data' => new ProcedureCategoryResource($procedureCategory)
            ]);

        } catch (\Exception $e) {
            Log::error('Błąd podczas pobierania kategorii: ' . $e->getMessage());
            return response()->json(['message' => 'Wystąpił błąd podczas pobierania kategorii'], 500);
        }
    }

    /**
     * Zaktualizuj określoną kategorię w bazie danych.
     */
    public function update(UpdateProcedureCategoryRequest $request, ProcedureCategory $procedureCategory): JsonResponse
    {
        try {
            $this->authorize('update', $procedureCategory);
            $validated = $request->validated();

            $validated['slug'] = $validated['slug'] ?? '';

            $procedureCategory->update($validated);

            return response()->json([
                'data' => new ProcedureCategoryResource($procedureCategory),
                'message' => 'Kategoria została zaktualizowana'
            ]);
        } catch (\Exception $e) {
            Log::error('Błąd podczas aktualizacji kategorii: ' . $e->getMessage());

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Błąd walidacji danych',
                    'errors' => $e->errors()
                ], 422);
            }

            return response()->json(['message' => 'Wystąpił błąd podczas aktualizacji kategorii'], 500);
        }
    }

    /**
     * Usuń określoną kategorię z bazy danych.
     */
    public function destroy(ProcedureCategory $procedureCategory): JsonResponse
    {
        try {
            $this->authorize('delete', $procedureCategory);
            $procedureCategory->delete();

            return response()->noContent();

        } catch (\Exception $e) {
            Log::error('Błąd podczas usuwania kategorii: ' . $e->getMessage());
            return response()->json(['message' => 'Wystąpił błąd podczas usuwania kategorii'], 500);
        }
    }
}
