// resources/views/errors/401.blade.php
@extends('layouts.error')

@section('title', 'Nieautoryzowany dostęp')

@section('content')
    <div class="error-container">
        <h1 class="error-code">401</h1>
        <h2 class="error-title">Nieautoryzowany dostęp</h2>
        <p class="error-message">Nie masz uprawnień do wyświetlenia tej strony. Zaloguj się, aby kontynuować.</p>
        <a href="{{ route('login') }}" class="btn btn-primary">Zaloguj się</a>
    </div>
@endsection
