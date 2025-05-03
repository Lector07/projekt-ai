<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth;
use App\Http\Controllers\Api\V1\Auth\NewPasswordController;


Route::prefix('api/v1')->name('api.v1.')->group(function () {
    // Istniejące trasy
    Route::post('/register', [Auth\RegisterController::class, 'store'])
        ->middleware('guest')
        ->name('register');

    Route::post('/login', [Auth\LoginController::class, 'store'])
        ->middleware('guest')
        ->name('login');

    Route::post('/logout', [Auth\LogoutController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    // Dodaj endpoint do pobierania danych zalogowanego użytkownika
    Route::get('/user', [Auth\UserController::class, 'show'])
        ->middleware('auth')
        ->name('user');
});

Route::post('/api/v1/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('api.v1.reset-password');

Route::post('/api/v1/forgot-password', [Auth\ForgotPasswordLinkController::class, 'store'])
    ->middleware('guest')
    ->name('api.v1.forgot-password');



Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
