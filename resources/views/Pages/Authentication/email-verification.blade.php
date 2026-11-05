@php($page = 'email-verification')
@extends('layout.mainlayout')

@push('styles')
<style>
    html, body { min-height: 100%; background: #f4f7fb; }
    .sidebar, .header, .footer, .settings-icon { display: none !important; }
    .main-wrapper, .page-wrapper { margin: 0 !important; min-height: 100vh; width: 100% !important; }
    .verification-page { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: linear-gradient(145deg, #f7f9fc 0%, #edf3fb 100%); }
    .verification-box { width: min(100%, 460px); overflow: hidden; background: #fff; border: 1px solid #d8e1ed; border-radius: 8px; box-shadow: 0 22px 55px rgba(7, 27, 63, .12); }
    .verification-brand { padding: 22px 28px; color: #fff; background: #08275f; display: flex; align-items: center; gap: 12px; }
    .verification-brand img { width: 54px; height: 54px; object-fit: contain; background: #fff; border-radius: 6px; padding: 4px; }
    .verification-brand strong { font-size: 21px; }
    .verification-body { padding: 30px 28px; }
    .verification-body h1 { color: #071b3f; font-size: 26px; margin-bottom: 10px; }
    .verification-body p { color: #5a687b; line-height: 1.6; }
    .verification-body label { color: #182b49; font-weight: 700; margin-bottom: 8px; }
    .verification-input { min-height: 54px; border: 1px solid #bdcadb; border-radius: 6px; font-size: 25px; letter-spacing: 8px; text-align: center; }
    .verification-submit { min-height: 50px; width: 100%; border-radius: 6px; font-weight: 700; background: #145dcc; border-color: #145dcc; }
    .verification-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 18px; }
</style>
@endpush

@section('content')
<main class="verification-page">
    <section class="verification-box" aria-labelledby="verification-title">
        <div class="verification-brand">
            <img src="{{ asset('/assets/img/saas-login-smat15.png') }}" alt="SmartProbook">
            <strong>SmartProbook</strong>
        </div>
        <div class="verification-body">
            <h1 id="verification-title">Verify your email</h1>
            <p>We sent a six-digit code to <strong>{{ $email }}</strong>. Enter it to verify your email and continue. The code expires after 10 minutes.</p>
            @if(session('success') || session('info'))
                <div class="alert alert-success" role="status">{{ session('success') ?? session('info') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
            @elseif(session('warning'))
                <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
            @endif
            @if($errors->has('code'))
                <div class="alert alert-danger" role="alert">{{ $errors->first('code') }}</div>
            @endif
            <form method="POST" action="{{ route('email-verification.verify') }}">
                @csrf
                <label for="code">Six-digit email code</label>
                <input id="code" class="form-control verification-input @error('code') is-invalid @enderror"
                    name="code" value="{{ old('code') }}" autocomplete="one-time-code"
                    inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus>
                <button class="btn btn-primary verification-submit mt-3" type="submit">Verify email and continue</button>
            </form>
            <div class="verification-actions">
                <form method="POST" action="{{ route('email-verification.resend') }}">
                    @csrf
                    <button class="btn btn-link p-0" type="submit">Resend code</button>
                </form>
                <a href="{{ route('logout') }}">Cancel and sign out</a>
            </div>
        </div>
    </section>
</main>
@endsection
