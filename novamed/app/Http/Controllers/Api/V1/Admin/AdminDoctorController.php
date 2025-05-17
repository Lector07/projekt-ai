<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreDoctorRequest;
use App\Http\Requests\Api\V1\Admin\UpdateDoctorRequest;
use App\Http\Resources\Api\V1\DoctorResource;
use App\Models\Doctor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Api\V1\Admin\UpdateDoctorAvatarRequest;

class AdminDoctorController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Doctor::class);

        $query = Doctor::query()->with('user');

        // Wyszukiwanie
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                        $userQuery->where('email', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Filtrowanie po specjalizacji
        if ($request->has('specialization') && !empty($request->specialization)) {
            $query->where('specialization', $request->specialization);
        }

        // Sortowanie
        $query->orderBy('last_name')->orderBy('first_name');

        // Paginacja
        $perPage = $request->input('per_page', 10);
        $doctors = $query->paginate($perPage);

        return DoctorResource::collection($doctors);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $this->authorize('create', Doctor::class);
        $validated = $request->validated();
        // TODO: Logika zapisu zdjęcia, jeśli jest w $validated['profile_picture']
        // Na razie tworzymy bez zdjęcia
        $doctor = Doctor::create(collect($validated)->except('profile_picture')->toArray());

        return (new DoctorResource($doctor))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor): DoctorResource
    {
        $this->authorize('view', $doctor);
        return new DoctorResource($doctor->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        $this->authorize('update', $doctor);
        $validated = $request->validated();
        // TODO: Logika aktualizacji/usuwania zdjęcia, jeśli jest w $validated['profile_picture']
        $doctor->update(collect($validated)->except('profile_picture')->toArray());

        return new DoctorResource($doctor->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor): Response
    {
        $this->authorize('delete', $doctor);

        // Usuwanie zdjęcia jeśli istnieje
        if ($doctor->profile_picture_path) {
            Storage::disk('public')->delete($doctor->profile_picture_path);
        }

        $doctor->delete();
        return response()->noContent();
    }

    public function updateAvatar(UpdateDoctorAvatarRequest $request, Doctor $doctor): DoctorResource
    {
        $this->authorize('update', $doctor);

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($doctor->profile_picture_path) {
                Storage::disk('public')->delete($doctor->profile_picture_path);
            }

            $path = $request->file('avatar')->store('avatars/doctors', 'public');
            $doctor->profile_picture_path = $path;
            $doctor->save();
        }

        return new DoctorResource($doctor->fresh());
    }
}
