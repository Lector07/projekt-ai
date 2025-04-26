<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth; // Importuj kontrolery Auth

// Trasy dla uwierzytelniania SPA (używają middleware 'web' dla sesji)
Route::prefix('api/v1')->name('api.v1.')->group(function () { // Dodaj też name() dla grupy
    Route::post('/register', [Auth\RegisterController::class, 'store'])
        ->middleware('guest') // Tylko goście
        ->name('register'); // Nazwa będzie api.v1.register

    Route::post('/login', [Auth\LoginController::class, 'store'])
        ->middleware('guest') // Tylko goście
        ->name('login'); // Nazwa będzie api.v1.login

    Route::post('/logout', [Auth\LogoutController::class, 'destroy'])
        ->middleware('auth') // Tylko zalogowani
        ->name('logout'); // Nazwa będzie api.v1.logout
});

// Catch-all route dla SPA Vue - musi być na samym końcu!
Route::get('/{any?}', function () { // Dodaj '?' do {any}, aby obsłużyć też stronę główną
    // Zwraca główny plik Blade, który ładuje Vue
    // Upewnij się, że widok 'app' istnieje w resources/views/
    return view('app');
})->where('any', '.*'); // Pozwala na dowolne znaki w ścieżce

// Usuń: require __DIR__.'/settings.php'; jeśli te trasy mają być częścią API
// lub przenieś je do routes/api.php.
