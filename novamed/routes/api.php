<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Auth;
use App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\V1\Admin;

Route::prefix('v1')->group(function () {
    Route::post('/login', [Auth\LoginController::class, 'store']);
    Route::post('/logout', [Auth\LogoutController::class, 'destroy'])->middleware('auth:sanctum');

    Route::apiResource('/procedures', V1\ProcedureController::class)->only(['index', 'show']);
    Route::apiResource('/doctors', V1\DoctorController::class)->only(['index', 'show']);
    Route::get('/appointments/check-availability', [V1\PatientAppointmentController::class, 'checkAvailability'])->name('api.v1.appointments.check');

    Route::middleware('auth:sanctum')->group(function () {
        Route::put('/user/password', [Auth\PasswordController::class, 'update']);

        Route::get('/user/profile', [V1\UserProfileController::class, 'show'])->name('api.v1.user.profile.show');
        Route::put('/user/profile', [V1\UserProfileController::class, 'update'])->name('api.v1.user.profile.update');
        Route::delete('/user/profile', [V1\UserProfileController::class, 'destroy'])->name('api.v1.user.profile.destroy');

        Route::apiResource('/patient/appointments', V1\PatientAppointmentController::class)->except(['update']);

        Route::prefix('admin')->middleware('auth.admin')->name('api.v1.admin.')->group(function () {
            Route::get('/dashboard', [Admin\AdminDashboardController::class, 'index'])->name('dashboard');

            Route::apiResource('/users', Admin\AdminUserController::class);
            Route::apiResource('/doctors', Admin\AdminDoctorController::class);
            Route::apiResource('/procedures', Admin\AdminProcedureController::class);
            Route::apiResource('/appointments', Admin\AdminAppointmentController::class);
        });
    });

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user()->load('roles');
    });
});
