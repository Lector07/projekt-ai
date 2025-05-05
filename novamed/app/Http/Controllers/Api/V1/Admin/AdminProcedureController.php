<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreProcedureRequest;   // <<< Import
use App\Http\Requests\Api\V1\Admin\UpdateProcedureRequest;   // <<< Import
use App\Http\Resources\Api\V1\ProcedureResource;            // <<< Import
use App\Models\Procedure;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AdminProcedureController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Procedure::class);
        $procedures = Procedure::with('category')->orderBy('name')->paginate(15);
        return ProcedureResource::collection($procedures);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProcedureRequest $request): JsonResponse
    {
        $this->authorize('create', Procedure::class);
        $validated = $request->validated();
        $procedure = Procedure::create($validated);

        return (new ProcedureResource($procedure->load('category'))) // Załaduj kategorię dla odpowiedzi
        ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Procedure $procedure): ProcedureResource
    {
        $this->authorize('view', $procedure);
        return new ProcedureResource($procedure->load('category')); // Załaduj kategorię
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProcedureRequest $request, Procedure $procedure): ProcedureResource
    {
        $this->authorize('update', $procedure);
        $validated = $request->validated();
        $procedure->update($validated);

        return new ProcedureResource($procedure->fresh()->load('category')); // Załaduj kategorię
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
