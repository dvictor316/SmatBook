<?php $page = 'error-404'; ?>
@extends('layout.mainlayout')
@section('content')

    <div class="main-wrapper">
        <div class="error-box">
            <h1>404</h1>
            <h3 class="h2 mb-3">
                <i class="fas fa-exclamation-circle text-warning"></i> Oops! Page not found!
            </h3>
            <p class="h4 font-weight-normal">The page you requested was not found.</p>
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="fas fa-home me-1"></i> Back to Home
            </a>
        </div>
    </div>

    <style>
        /* Center the error box if not already handled by your theme */
        .error-box {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            min-height: 80vh;
            text-align: center;
        }
        .error-box h1 {
            font-size: 10rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            color: #1b5a90; /* Matching your theme blue */
        }
    </style>
@endsection