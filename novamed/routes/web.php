<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth;
use App\Http\Controllers\Api\V1\Auth\NewPasswordController;


Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
