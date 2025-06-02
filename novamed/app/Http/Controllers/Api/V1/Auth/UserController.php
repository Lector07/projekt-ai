<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Api\V1\UserResource; // Import UserResource

class UserController extends Controller
{
    /**
     * Pobierz dane zalogowanego użytkownika.
     *
     * Dane zostaną sformatowane przez UserResource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\Api\V1\UserResource
     */
    public function show(Request $request): UserResource
    {
        // Po prostu zwracamy UserResource dla aktualnie zalogowanego użytkownika.
        // UserResource zajmie się całą logiką formatowania, w tym generowaniem
        // URL-a do avatara i dodaniem pola 'role'.
        return new UserResource($request->user());
    }
}
