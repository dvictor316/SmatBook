@extends('errors.minimal')

@section('title', 'Page Not Found | SmartProbook')

@section('content')
    <p class="error-code">Error 404</p>
    <h1>Page not found</h1>
    <p>The page may have moved, the address may be incorrect, or you may not have access to it.</p>
    <a href="{{ url('/') }}" class="error-action">Return to SmartProbook</a>
@endsection
