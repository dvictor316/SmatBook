@extends('errors.minimal')

@section('title', 'Server Error | SmartProbook')

@section('content')
    <p class="error-code">Error 500</p>
    <h1>We could not complete that request</h1>
    <p>Please try again. If the problem continues, contact your administrator or SmartProbook support.</p>

    @if (!empty($errorMessage))
        <p class="error-detail">{{ $errorMessage }}</p>
    @endif

    <a href="{{ url('/') }}" class="error-action">Return to SmartProbook</a>
@endsection
