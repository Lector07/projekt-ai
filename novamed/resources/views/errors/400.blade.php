@extends('layouts.error')

@section('title', 'Nieprawidłowe żądanie')

@section('content')
    <div class="error-container">
        <h1 class="error-code">400</h1>
        <h2 class="error-title">Nieprawidłowe żądanie</h2>
        <p class="error-message">Serwer nie może przetworzyć tego żądania z powodu błędnej składni.</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Strona główna</a>
    </div>
@endsection
