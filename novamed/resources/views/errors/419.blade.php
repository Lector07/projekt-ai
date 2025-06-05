@extends('layouts.error')

@section('title', 'Strona wygasła')

@section('content')
    <div class="error-container">
        <h1 class="error-code">419</h1>
        <h2 class="error-title">Strona wygasła</h2>
        <p class="error-message">Twoja sesja wygasła. Odśwież stronę i spróbuj ponownie.</p>
        <a href="{{ url()->current() }}" class="btn btn-primary">Odśwież stronę</a>
    </div>
@endsection
