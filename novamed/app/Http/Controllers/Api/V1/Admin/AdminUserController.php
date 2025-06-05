<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AdminUpdateUserAvatarRequest;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUserRequest;
use App\Http\Requests\Api\V1\UpdateUserAvatarRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminUserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->orderBy('id', 'asc');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $validated = $request->validated();

        $userData = collect($validated)->except('password')->toArray();
        $userData['password'] = Hash::make($validated['password']);
        $userData['role'] = $validated['role'] ?? 'patient';

        $user = User::create($userData);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);
        $validated = $request->validated();

        $userData = collect($validated)->except('password')->toArray();

        $user->update($userData);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        return new UserResource($user->fresh());
    }

    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);
        $user->delete();
        return response()->noContent();
    }

    /**
     * Zwraca listę pacjentów dla formularzy wyboru
     */
    public function patients(): JsonResponse
    {
        $patients = User::where('role', 'patient')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $patients]);
    }

    public function updateAvatar(AdminUpdateUserAvatarRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($user->profile_picture_path) {
                Storage::disk('public')->delete($user->profile_picture_path);
            }

            $path = $request->file('avatar')->store('avatars/users', 'public');
            $user->profile_picture_path = $path;
            $user->save();
        }

        return new UserResource($user->fresh());
    }

    /**
     * Usuwa avatar użytkownika
     */
    public function deleteAvatar(User $user): UserResource
    {
        $this->authorize('update', $user);

        if ($user->profile_picture_path) {
            Storage::disk('public')->delete($user->profile_picture_path);
            $user->profile_picture_path = null;
            $user->save();
        }

        return new UserResource($user->fresh());
    }
}
