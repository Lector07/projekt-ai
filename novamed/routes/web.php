<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth;
use App\Http\Controllers\Api\V1\Auth\NewPasswordController;


Route::prefix('api/v1')->name('api.v1.')->group(function () {
    Route::post('/register', [Auth\RegisterController::class, 'store'])
        ->middleware('guest')
        ->name('register');

    Route::post('/login', [Auth\LoginController::class, 'store'])
        ->middleware('guest')
        ->name('login');

    Route::post('/logout', [Auth\LogoutController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');
});

Route::post('/api/v1/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('api.v1.reset-password');



Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');

