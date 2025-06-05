@extends('layouts.error')

@section('title', 'Dostęp zabroniony')

@section('content')
    <div class="error-container">
        <h1 class="error-code">403</h1>
        <h2 class="error-title">Dostęp zabroniony</h2>
        <p class="error-message">Nie masz uprawnień do dostępu do tego zasobu.</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Strona główna</a>
    </div>
@endsection
