<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreDoctorRequest;
use App\Http\Requests\Api\V1\Admin\UpdateDoctorRequest;
use App\Http\Resources\Api\V1\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
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

        if ($request->has('specialization') && !empty($request->specialization)) {
            $query->where('specialization', $request->specialization);
        }

        $query->orderBy('id')->orderBy('first_name');

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

        try {
            $doctor = new Doctor();
            $doctor->first_name = $validated['first_name'];
            $doctor->last_name = $validated['last_name'];
            $doctor->specialization = $validated['specialization'];

            if (isset($validated['bio'])) $doctor->bio = $validated['bio'];
            if (isset($validated['price_modifier'])) $doctor->price_modifier = $validated['price_modifier'];

            if (isset($validated['user_id']) && !empty($validated['user_id'])) {
                $doctor->user_id = $validated['user_id'];
                $doctor->save();
            } else {
                if (isset($validated['email']) && isset($validated['password'])) {
                    $doctor->save();

                    $user = new User();
                    $user->name = $validated['first_name'] . ' ' . $validated['last_name'];
                    $user->email = $validated['email'];
                    $user->password = Hash::make($validated['password']);
                    $user->role = 'doctor';
                    $user->save();

                    $doctor->user_id = $user->id;
                    $doctor->save();
                } else {
                    $doctor->save();
                }
            }

            $doctor->refresh();
            $doctor->load('user');

            return (new DoctorResource($doctor))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Wystąpił błąd: ' . $e->getMessage()], 500);
        }
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

    public function list(): JsonResponse
    {
        $doctors = Doctor::all()->map(function ($doctor) {
            return [
                'id' => $doctor->id,
                'name' => $doctor->first_name . ' ' . $doctor->last_name
            ];
        });

        return response()->json(['data' => $doctors]);
    }
}
