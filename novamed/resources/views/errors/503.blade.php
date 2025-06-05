@extends('layouts.error')

@section('title', 'Usługa niedostępna')

@section('content')
    <div class="error-container">
        <h1 class="error-code">503</h1>
        <h2 class="error-title">Usługa tymczasowo niedostępna</h2>
        <p class="error-message">Przepraszamy, serwis jest obecnie w trybie konserwacji. Spróbuj ponownie później.</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Odśwież stronę</a>
    </div>
@endsection
