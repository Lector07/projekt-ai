@echo off
echo ====================================
echo Uruchamianie projektu Laravel + Vue
echo ====================================
echo.

REM Sprawdzenie czy wymagane narzędzia są zainstalowane
where php >nul 2>&1 || (
    echo Nie znaleziono PHP! Zainstaluj PHP i dodaj do PATH.
    exit /b 1
)

where composer >nul 2>&1 || (
    echo Nie znaleziono Composer! Zainstaluj Composer i dodaj do PATH.
    exit /b 1
)

where npm >nul 2>&1 || (
    echo Nie znaleziono npm! Zainstaluj Node.js i npm.
    exit /b 1
)

echo Instalacja zależności PHP...
call composer install
if %errorlevel% neq 0 (
    echo Błąd podczas instalacji zależności PHP!
    exit /b 1
)

echo.
echo Instalacja zależności JavaScript...
call npm install
if %errorlevel% neq 0 (
    echo Błąd podczas instalacji zależności JavaScript!
    exit /b 1
)

echo.
echo Konfiguracja środowiska...
if not exist .env (
    copy .env.example .env
    php artisan key:generate
)

echo.
echo Uruchamianie migracji bazy danych...
php artisan migrate
if %errorlevel% neq 0 (
    echo Ostrzeżenie: Wystąpił problem z migracją bazy danych!
    choice /C YN /M "Czy chcesz kontynuować mimo to?"
    if errorlevel 2 exit /b 1
)

echo.
echo Uruchamianie aplikacji...
start cmd /k php artisan serve
timeout /t 2 >nul

echo.
echo Uruchamianie Vite dla frontendu...
start cmd /k npm run dev

echo.
echo ====================================
echo Aplikacja uruchomiona!
echo Backend: http://localhost:8000
echo ====================================

exit /b 0
