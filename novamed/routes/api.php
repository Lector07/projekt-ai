<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Auth;
use App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\V1\Admin;

Route::prefix('v1')->group(function () {
    // Trasy publiczne - autentykacja
    Route::post('/login', [Auth\LoginController::class, 'store']);
    Route::post('/logout', [Auth\LogoutController::class, 'destroy'])->middleware('auth:sanctum');
    Route::post('/register', [Auth\RegisterController::class, 'register']);
    Route::post('/reset-password', [Auth\ForgotPasswordLinkController::class, 'reset']);
    Route::post('/forgot-password', [Auth\ForgotPasswordLinkController::class, 'store']);

    // Zasoby publiczne
    Route::apiResource('/procedures', V1\ProcedureController::class)->only(['index', 'show']);
    Route::apiResource('/doctors', V1\DoctorController::class)->only(['index', 'show']);
    Route::get('/appointments/check-availability', [V1\PatientAppointmentController::class, 'checkAvailability'])
        ->name('api.v1.appointments.check');

    // Trasy wymagające uwierzytelnienia
    Route::middleware('auth:sanctum')->group(function () {
        // Profil użytkownika
        Route::get('/user', function (Request $request) {
            return $request->user()->load('roles');
        });
        Route::put('/user/password', [Auth\PasswordController::class, 'update']);
        Route::get('/user/profile', [V1\UserProfileController::class, 'show'])->name('api.v1.user.profile.show');
        Route::put('/user/profile', [V1\UserProfileController::class, 'update'])->name('api.v1.user.profile.update');
        Route::delete('/user/profile', [V1\UserProfileController::class, 'destroy'])->name('api.v1.user.profile.destroy');

        // Trasy dla pacjenta
        Route::prefix('patient')->group(function () {
            Route::apiResource('appointments', V1\PatientAppointmentController::class);
            Route::get('appointments/check-availability', [V1\PatientAppointmentController::class, 'checkAvailability']);
        });

        // Trasy dla administratora
        Route::prefix('admin')->middleware('auth.admin')->name('api.v1.admin.')->group(function () {
            Route::get('/dashboard', [Admin\AdminDashboardController::class, 'index'])->name('dashboard');
            Route::apiResource('/users', Admin\AdminUserController::class);
            Route::apiResource('/doctors', Admin\AdminDoctorController::class);
            Route::apiResource('/procedures', Admin\AdminProcedureController::class);
            Route::apiResource('/appointments', Admin\AdminAppointmentController::class);
        });
    });
});
