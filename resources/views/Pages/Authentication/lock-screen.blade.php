<?php $page = 'lock-screen'; ?>
@extends('layout.mainlayout')

@section('content')
{{-- User requested print script integration --}}
<script>
    // Global print configuration for this page
    window.onbeforeprint = function() {
        console.log("Preparing page for printing...");
    };
</script>

<div class="auth-container" style="min-height: 100vh; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
    <div class="login-card-custom" style="background: #fff; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; max-width: 500px; width: 100%; border: 1px solid #eee;">
        <div class="p-5">
            <div class="text-center mb-4">
                <img src="{{ URL::asset('/assets/img/smat14.png') }}" class="mb-4" style="max-height: 50px;" alt="Logo">
                
                <div class="lock-user mb-4">
                    <div class="position-relative d-inline-block">
                        <img class="rounded-circle img-thumbnail shadow-sm" 
                             src="{{ session('lock_user_image') ?? URL::asset('/assets/img/profiles/avatar-02.jpg') }}" 
                             alt="User Image" 
                             style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #3d5ee1;">
                        <span class="position-absolute bottom-0 end-0 bg-primary border border-light rounded-circle p-2" title="Locked">
                            <i class="fas fa-lock text-white" style="font-size: 10px;"></i>
                        </span>
                    </div>
                    <h4 class="mt-3 fw-bold text-dark">{{ session('lock_user_name') ?? 'User' }}</h4>
                    <p class="text-muted small">{{ session('lock_user_email') ?? 'Session Locked' }}</p>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 0.5rem;"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('login-account.post') }}">
                @csrf
                <div class="input-block mb-4">
                    <label class="form-label small fw-bold">Password</label>
                    <div class="pass-group">
                        <input class="form-control @error('password') is-invalid @enderror" 
                               type="password" 
                               name="password" 
                               placeholder="Enter password to unlock" 
                               required 
                               autofocus>
                        <span class="fas fa-eye-slash toggle-password"></span>
                    </div>
                </div>

                <button class="btn btn-primary btn-lg w-100 fw-bold shadow-sm mb-3" type="submit">
                    Unlock Account
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="small text-muted mb-0">Not you? 
                    <a href="{{ route('saas-login') }}" class="text-primary fw-bold text-decoration-none">Sign in as a different user</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection