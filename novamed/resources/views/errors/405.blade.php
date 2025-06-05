@extends('layouts.error')

@section('title', 'Niedozwolona metoda')

@section('content')
    <div class="error-container">
        <h1 class="error-code">405</h1>
        <h2 class="error-title">Niedozwolona metoda</h2>
        <p class="error-message">Metoda użyta w żądaniu nie jest obsługiwana dla tego zasobu.</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Strona główna</a>
    </div>
@endsection
