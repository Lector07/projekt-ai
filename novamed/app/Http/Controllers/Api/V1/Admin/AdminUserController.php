<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    use AuthorizesRequests;

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);
        $users = User::orderBy('name')->paginate(15); // Usunięto with('roles')
        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $validated = $request->validated();

        // Pole 'role' jest już w $validated dzięki Form Request
        $userData = collect($validated)->except('password')->toArray();
        $userData['password'] = Hash::make($validated['password']);

        $user = User::create($userData);
        // Nie ma potrzeby synchronizacji ról

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);
        // Nie ma potrzeby ładowania ról
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);
        $validated = $request->validated();

        // Pole 'role' jest już w $validated
        $userData = collect($validated)->except('password')->toArray();

        $user->update($userData);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }
        // Nie ma potrzeby synchronizacji ról

        return new UserResource($user->fresh());
    }

    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);
        $user->delete();
        return response()->noContent();
    }
}
