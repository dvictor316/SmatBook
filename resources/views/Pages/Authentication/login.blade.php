<?php $page = 'login'; ?>
@extends('layout.mainlayout')
@section('content')

<script>
    window.onbeforeprint = function() {
        console.log("Preparing login page for printing...");
    };
</script>

<div class="login-wrapper">
    <div class="container">

        <div class="text-center mb-4">
            @php
                // Logic to fetch client logo based on domain or session
                // Fallback to smat14 if no client logo is found
                $clientLogo = isset($company) && $company->logo ? asset('storage/' . $company->logo) : URL::asset('/assets/img/logos.png');
            @endphp
            <x-auth-brand-lockup :logo="$clientLogo" size="md" />
        </div>

        <div class="loginbox">
            <div class="row g-0">

                <div class="col-lg-6 d-none d-lg-block">
                    <div class="authentication-wrapper h-100" style="background: #3d5ee1; display: flex; align-items: center; justify-content: center; padding: 20px;">
                        <img src="{{ URL::asset('/assets/img/logos.png') }}" class="img-fluid" alt="SmartProbook Login Visual">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="login-right">
                        <div class="login-right-wrap p-4">
                            <h1>Login</h1>
                            <p class="account-subtitle">Access to our dashboard</p>

                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show small py-2" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('saas-login.post') }}">
                                @csrf

                                <div class="input-block mb-3">
                                    <label class="form-control-label">Email Address</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
                                </div>

                                <div class="input-block mb-3">
                                    <label class="form-control-label">Password</label>
                                    <div class="pass-group">
                                        <input type="password" name="password" class="form-control pass-input @error('password') is-invalid @enderror" placeholder="Enter your password" required>
                                        <span class="fa-solid fa-eye-slash toggle-password"></span>
                                    </div>
                                </div>

                                <div class="input-block mb-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-check custom-checkbox">
                                                <input type="checkbox" class="form-check-input" id="cb1" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="cb1">Remember me</label>
                                            </div>
                                        </div>
                                        <div class="col-6 text-end">
                                            <a class="forgot-link small" href="{{ route('password.request') }}">Forgot Password?</a>
                                        </div>
                                    </div>
                                </div>

                                <button class="btn btn-lg btn-primary w-100 fw-bold shadow-sm" type="submit">Login</button>

                                <div class="login-or my-4">
                                    <span class="or-line"></span>
                                    <span class="span-or">or</span>
                                </div>

                                <div class="social-login mb-3 text-center">
                                    <p class="small text-muted mb-2">Login with</p>
                                    <a href="{{ route('social.login', 'facebook') }}" class="btn btn-outline-light border me-2"><i class="fab fa-facebook-f text-primary"></i></a>
                                    <a href="{{ route('social.login', 'google') }}" class="btn btn-outline-light border"><i class="fab fa-google text-danger"></i></a>
                                </div>

                                @if(!isset($settings) || $settings->allow_registration)
                                    <div class="text-center dont-have mt-3">
                                        Don't have an account yet? <a href="{{ route('saas-register') }}" class="text-primary fw-bold">Register</a>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
    </div>
</div>
@endsection
