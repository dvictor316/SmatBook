<?php $page = 'reset-password'; ?>
@extends('layout.mainlayout')
@section('content')

<div class="auth-container" style="min-height: 100vh; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="login-card-custom" style="background: #fff; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; max-width: 1000px; width: 100%;">
        <div class="row g-0">
            
            {{-- Left Side: Branding Banner --}}
            <div class="col-lg-6 d-none d-lg-block" style="background: #3d5ee1; position: relative;">
                <div class="h-100 d-flex flex-column align-items-center justify-content-center p-5 text-center text-white">
                    {{-- Logo on the left side banner --}}
                    <img src="{{ asset('/assets/img/logos.png') }}" class="img-fluid mb-4" alt="SmartProbook Branding" style="max-height: 600px; object-fit: contain;">
                    <h2 class="fw-bold">Almost there!</h2>
                    <p class="opacity-75">Secure your account by choosing a new, strong password. Make sure it's something you'll remember.</p>
                </div>
            </div>

            {{-- Right Side: Reset Password Form --}}
            <div class="col-lg-6 p-4 p-md-5">
                <div class="mb-4 text-center">
                    {{-- Logo above the form --}}
                    <img src="{{ asset('/assets/img/logos.png') }}" 
                         class="mb-3" 
                         style="max-height: 100px; width: auto;" 
                         alt="Logo">
                    
                    <h3 class="fw-bold">Set New Password</h3>
                    <p class="text-muted">Enter your email and your new password below.</p>
                </div>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    
                    {{-- Password Reset Token (Hidden) --}}
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                               value="{{ $email ?? old('email') }}" required {{ !empty($email) ? 'readonly' : '' }}>
                        @error('email')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Password</label>
                        <div class="pass-group">
                            <input type="password" name="password" class="form-control pass-input @error('password') is-invalid @enderror" placeholder="••••••••" required autofocus>
                            <span class="fas fa-eye-slash toggle-password"></span>
                        </div>
                        @error('password')
                            <span class="text-danger small"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Confirm New Password</label>
                        <div class="pass-group">
                            <input type="password" name="password_confirmation" class="form-control pass-input-two" placeholder="••••••••" required>
                            <span class="fas fa-eye-slash toggle-password-two"></span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                        Update Password & Log In
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection
