<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Api\V1\UserResource;

class UserController extends Controller
{

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
