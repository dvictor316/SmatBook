@extends('errors.minimal')

@section('title', 'Service Temporarily Unavailable | SmartProbook')

@section('content')
    <p class="error-code">Service unavailable</p>
    <h1>SmartProbook is temporarily unavailable</h1>
    <p>{{ $message ?? 'We cannot reach the database at the moment. Please wait briefly and try again.' }}</p>
    <a href="{{ url()->current() }}" class="error-action">Try Again</a>
@endsection
