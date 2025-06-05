@extends('layouts.error')

@section('title', 'Strona nie znaleziona')

@section('content')
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Strona nie znaleziona</h2>
        <p class="error-message">Przepraszamy, ale strona której szukasz nie istnieje lub została przeniesiona.</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Strona główna</a>
    </div>
@endsection
