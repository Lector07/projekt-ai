<?php

use App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\V1\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AdminDoctorController;
use App\Http\Controllers\Api\V1\Admin\AdminProcedureCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
// use App\Http\Controllers\Api\V1\Auth\RegisterController;

Route::prefix('v1')->name('v1.')->group(function () {

    // ... inne trasy publiczne V1 ...
    Route::get('/procedures/categories', [V1\ProcedureController::class, 'categories'])->name('procedures.categories');
    Route::apiResource('/procedures', V1\ProcedureController::class)->only(['index', 'show'])->names('procedures');
    Route::apiResource('/doctors', V1\DoctorController::class)->only(['index', 'show'])->names('doctors.public');
    Route::get('/appointments/check-availability', [V1\PatientAppointmentController::class, 'checkAvailability'])
        ->name('appointments.check');
    Route::get('/doctors/{doctor}/availability', [V1\DoctorController::class, 'getAvailability'])
        ->name('doctors.availability');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            // <<< TUTAJ DODAJ LOGOWANIE LUB DD >>>
            \Illuminate\Support\Facades\Log::info('GET /api/v1/user route hit with UserResource. User ID: ' . $request->user()->id . ', Role: ' . $request->user()->role . ', Path: ' . $request->user()->profile_picture_path);

            // Alternatywnie, aby zatrzymać wykonanie i zobaczyć dane użytkownika:
            // dd($request->user()->toArray(), $request->user()->profile_picture_path, $request->user()->role);

            return new \App\Http\Resources\Api\V1\UserResource($request->user());
        })->name('user.show');

        // ... reszta tras profilu użytkownika i innych tras chronionych ...
        Route::get('/user/profile', [V1\UserProfileController::class, 'show'])->name('user.profile.show');
        Route::put('/user/profile', [V1\UserProfileController::class, 'update'])->name('user.profile.update');
        Route::post('/user/profile/avatar', [V1\UserProfileController::class, 'updateAvatar'])->name('user.profile.avatar.update');
        Route::put('/user/password', [V1\UserProfileController::class, 'updatePassword'])->name('user.password.update');
        Route::delete('/user/profile', [V1\UserProfileController::class, 'destroy'])->name('user.profile.destroy');

        Route::apiResource('/patient/appointments', V1\PatientAppointmentController::class)
            ->except(['update'])
            ->names('patient.appointments');

        Route::prefix('doctor')
            ->middleware('auth.doctor')
            ->name('doctor.')
            ->group(function () {
                Route::get('/profile', [V1\Doctor\DoctorProfileController::class, 'show'])->name('profile.show');
                Route::put('/profile', [V1\Doctor\DoctorProfileController::class, 'update'])->name('profile.update');
                Route::post('/profile/avatar', [V1\Doctor\DoctorProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
                Route::get('/appointments', [V1\Doctor\DoctorAppointmentController::class, 'index'])->name('appointments.index');
                Route::get('/appointments/{appointment}', [V1\Doctor\DoctorAppointmentController::class, 'show'])->name('appointments.show');
                Route::put('/appointments/{appointment}', [V1\Doctor\DoctorAppointmentController::class, 'update'])->name('appointments.update');
            });
    });
});

// ... reszta pliku routes/api.php (trasy administracyjne) ...
Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'auth.admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [Admin\AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/procedures/categories', [Admin\AdminProcedureController::class, 'categories'])
            ->name('procedures.categories');
        Route::apiResource('/users', Admin\AdminUserController::class)->names('users');
        Route::post('/users/{user}/avatar', [Admin\AdminUserController::class, 'updateAvatar'])->name('users.avatar.update'); // <<< --- NOWA TRASA
        Route::apiResource('procedure-categories', Admin\AdminProcedureCategoryController::class)->names('procedure-categories');
        Route::apiResource('/doctors', Admin\AdminDoctorController::class)->names('doctors');
        Route::apiResource('/procedures', Admin\AdminProcedureController::class)->names('procedures');
        Route::apiResource('/appointments', Admin\AdminAppointmentController::class)->names('appointments');
        Route::post('/doctors/{doctor}/avatar', [Admin\AdminDoctorController::class, 'updateAvatar'])->name('doctors.avatar.update');
    });
