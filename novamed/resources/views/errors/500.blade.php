@extends('layouts.error')

@section('title', 'Błąd serwera')

@section('content')
    <div class="error-container">
        <h1 class="error-code">500</h1>
        <h2 class="error-title">Wewnętrzny błąd serwera</h2>
        <p class="error-message">Przepraszamy, wystąpił nieoczekiwany błąd po stronie serwera.</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Strona główna</a>
    </div>
@endsection
