<?php

use App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\V1\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AdminProcedureCategoryController;

// Importuj kontrolery V1 i Admin

// WAŻNE: Najpierw definiujemy bardziej specyficzne trasy
Route::get('/procedures/categories', [V1\ProcedureController::class, 'categories']);
// Potem definiujemy ogólne trasy resource
Route::apiResource('/procedures', V1\ProcedureController::class)->only(['index', 'show']);

Route::apiResource('/doctors', V1\DoctorController::class)->only(['index', 'show']);
Route::get('/appointments/check-availability', [V1\PatientAppointmentController::class, 'checkAvailability'])
    ->name('appointments.check');


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\Api\V1\UserResource($request->user());
    })->name('user.show');


    Route::get('/user/profile', [V1\UserProfileController::class, 'show'])->name('user.profile.show');
    Route::put('/user/profile', [V1\UserProfileController::class, 'update'])->name('user.profile.update');
    Route::put('/user/password', [V1\UserProfileController::class, 'updatePassword'])->name('user.password.update');
    Route::delete('/user/profile', [V1\UserProfileController::class, 'destroy'])->name('user.profile.destroy');
    Route::post('/user/profile/avatar', [V1\UserProfileController::class, 'updateAvatar'])->name('user.profile.avatar.update');

    Route::apiResource('/patient/appointments', V1\PatientAppointmentController::class)
        ->except(['update'])
        ->names('patient.appointments');

    Route::prefix('v1/admin')
        ->middleware('auth.admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [Admin\AdminDashboardController::class, 'index']);

            // Trasa do kategorii MUSI być przed apiResource
            Route::get('/procedures/categories', [Admin\AdminProcedureController::class, 'categories'])
                ->name('procedures.categories');

            // Trasy apiResource poniżej
            Route::apiResource('/users', Admin\AdminUserController::class);
            Route::apiResource('procedure-categories', Admin\AdminProcedureCategoryController::class);
            Route::apiResource('/doctors', Admin\AdminDoctorController::class);
            Route::apiResource('/procedures', Admin\AdminProcedureController::class);
            Route::apiResource('/appointments', Admin\AdminAppointmentController::class);
            Route::post('/doctors/{doctor}/avatar', [Admin\AdminDoctorController::class, 'updateAvatar'])->name('doctors.avatar.update');
        });

    Route::prefix('doctor')
        ->middleware('auth.doctor')
        ->name('api.v1.doctor.')
        ->group(function () {
            Route::get('/profile', [V1\Doctor\DoctorProfileController::class, 'show'])->name('profile.show');
            Route::put('/profile', [V1\Doctor\DoctorProfileController::class, 'update'])->name('profile.update');
            Route::get('/appointments', [V1\Doctor\DoctorAppointmentController::class, 'index'])->name('appointments.index');
            Route::get('/appointments/{appointment}', [V1\Doctor\DoctorAppointmentController::class, 'show'])->name('appointments.show');
            Route::put('/appointments/{appointment}', [V1\Doctor\DoctorAppointmentController::class, 'update'])->name('appointments.update');
            Route::post('/profile/avatar', [V1\Doctor\DoctorProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
        });
});
