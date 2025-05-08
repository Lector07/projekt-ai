<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importuj kontrolery V1 i Admin
use App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\V1\Admin;
// Usunięto importy kontrolerów Auth, bo trasy są w web.php

// Zakładamy globalny prefiks api/v1 z bootstrap/app.php LUB dodaj Route::prefix('v1') na zewnątrz
// Route::prefix('v1')->group(function () { // Odkomentuj, jeśli nie ma globalnego prefiksu

// === Trasy Publiczne ===
Route::apiResource('/procedures', V1\ProcedureController::class)->only(['index', 'show']);
Route::apiResource('/doctors', V1\DoctorController::class)->only(['index', 'show']);
Route::get('/appointments/check-availability', [V1\PatientAppointmentController::class, 'checkAvailability'])
    ->name('appointments.check'); // Uproszczona nazwa

// === Trasy Wymagające Uwierzytelnienia ===
Route::middleware('auth:sanctum')->group(function () {

    // Endpoint do pobrania danych zalogowanego użytkownika (bez ładowania ról tutaj)
    Route::get('/user', function (Request $request) {
        // UserResource sam zdecyduje co zwrócić (w tym pole 'role')
        return new \App\Http\Resources\Api\V1\UserResource($request->user());
    })->name('user.show');

    // Profil użytkownika
    Route::get('/user/profile', [V1\UserProfileController::class, 'show'])->name('user.profile.show');
    Route::put('/user/profile', [V1\UserProfileController::class, 'update'])->name('user.profile.update');
    // Zmiana hasła (używamy metody w UserProfileController)
    Route::put('/user/password', [V1\UserProfileController::class, 'updatePassword'])->name('user.password.update');
    Route::delete('/user/profile', [V1\UserProfileController::class, 'destroy'])->name('user.profile.destroy');

    // Wizyty pacjenta
    Route::apiResource('/patient/appointments', V1\PatientAppointmentController::class)
        ->except(['update']) // Pacjent nie aktualizuje przez PUT/PATCH
        ->names('patient.appointments');

    // === Trasy Tylko dla Administratora ===
    Route::prefix('admin')
        ->middleware('auth.admin')
        ->name('admin.') // Prefiks nazw tras admin
        ->group(function () {
            Route::get('/dashboard', [Admin\AdminDashboardController::class, 'index'])->name('dashboard');
            Route::apiResource('/users', Admin\AdminUserController::class);
            Route::apiResource('/doctors', Admin\AdminDoctorController::class);
            Route::apiResource('/procedures', Admin\AdminProcedureController::class);
            Route::apiResource('/appointments', Admin\AdminAppointmentController::class);
        });

}); // Koniec grupy auth:sanctum

// }); // Koniec grupy v1 (jeśli używasz Route::prefix('v1') tutaj)
