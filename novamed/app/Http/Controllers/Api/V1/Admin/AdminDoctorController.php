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

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Doctor::class);

        $query = Doctor::query()->with(['user', 'procedures']);

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('specialization', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', function ($subQ) use ($searchTerm) {
                        $subQ->where('email', 'like', "%{$searchTerm}%");
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

            // Priorytetowo obsługujemy user_id, jeśli istnieje
            if (isset($validated['user_id']) && !empty($validated['user_id'])) {
                $user = User::findOrFail($validated['user_id']);
                $doctor->user()->associate($user);
            }
            // Tylko gdy nie podano user_id, próbujemy utworzyć nowego użytkownika
            else if (isset($validated['email']) && isset($validated['password'])) {
                $user = new User();
                $user->name = $validated['first_name'] . ' ' . $validated['last_name'];
                $user->email = $validated['email'];
                $user->password = Hash::make($validated['password']);
                $user->save();

                $user->assignRole('doctor');
                $doctor->user()->associate($user);
            }

            $doctor->save();

            if (isset($validated['procedure_ids']) && is_array($validated['procedure_ids'])) {
                $doctor->procedures()->sync($validated['procedure_ids']);
            }

            return (new DoctorResource($doctor->fresh()->load(['user', 'procedures'])))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Wystąpił błąd podczas dodawania lekarza.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Doctor $doctor): DoctorResource
    {
        $this->authorize('view', $doctor);
        return new DoctorResource($doctor->load(['user', 'procedures']));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        $this->authorize('update', $doctor);
        $validated = $request->validated();

        $doctor->update(collect($validated)->except(['profile_picture', 'procedure_ids'])->toArray());

        if (isset($validated['procedure_ids'])) {
            $doctor->procedures()->sync($validated['procedure_ids']);
        } elseif ($request->has('procedure_ids') && $validated['procedure_ids'] === null) {
            $doctor->procedures()->detach();
        }

        return new DoctorResource($doctor->fresh()->load(['user', 'procedures']));
    }

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

        return new DoctorResource($doctor->fresh()->load(['user', 'procedures']));
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
