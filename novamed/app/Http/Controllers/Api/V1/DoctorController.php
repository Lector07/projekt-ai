<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DoctorResource; // Upewnij się, że ten import jest obecny
use App\Models\Doctor;
use Illuminate\Http\Request; // Możesz go potrzebować do filtrowania/sortowania w przyszłości

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) // Dodaj Request $request
    {
        // Pobierz itemsPerPage z requestu, z domyślną wartością, jeśli nie podano
        $perPage = $request->input('per_page', 4); // Domyślnie 4, tak jak masz w DoctorsPage.vue

        $doctors = Doctor::paginate($perPage);
        return DoctorResource::collection($doctors); // <<< --- WAŻNA POPRAWKA
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ... logika ...
        // $newDoctor = Doctor::create($request->validated());
        // return (new DoctorResource($newDoctor))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor): DoctorResource
    {
        // $doctor->load('user'); // Jeśli potrzebujesz danych użytkownika
        return new DoctorResource($doctor);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Doctor $doctor)
    {
        // ... logika ...
        // $doctor->update($request->validated());
        // return new DoctorResource($doctor->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor)
    {
        // ... logika ...
        // return response()->noContent();
    }
}
