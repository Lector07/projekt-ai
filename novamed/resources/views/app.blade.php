<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Novamed') }}</title>

    <!-- Fonts (Opcjonalnie, jeśli nie ładujesz ich przez CSS/JS) -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Vite - Ładuje skompilowany JS i CSS dla Twojej SPA -->
    {{-- Upewnij się, że ścieżka do pliku wejściowego jest poprawna --}}
    @vite(['resources/js/app.ts'])
    @vite('resources/css/app.css')

</head>
<body class="font-sans antialiased">
{{-- Główny kontener, do którego Vue zamontuje aplikację --}}
{{-- ID musi pasować do tego używanego w resources/js/app.ts (np. createApp(App).mount('#app')) --}}
<div id="app"></div>
</body>
</html>
