@php($page = 'two-factor-challenge')
@extends('layout.mainlayout')

@push('styles')
<style>
    html, body { min-height: 100%; background: #f4f7fb; }
    .sidebar, .header, .footer, .settings-icon { display: none !important; }
    .main-wrapper, .page-wrapper { margin: 0 !important; min-height: 100vh; width: 100% !important; }
    .factor-page { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: linear-gradient(145deg, #f7f9fc 0%, #edf3fb 100%); }
    .factor-box { width: min(100%, 460px); overflow: hidden; background: #fff; border: 1px solid #d8e1ed; border-radius: 8px; box-shadow: 0 22px 55px rgba(7, 27, 63, .12); }
    .factor-brand { padding: 22px 28px; color: #fff; background: #08275f; display: flex; align-items: center; gap: 12px; }
    .factor-brand img { width: 54px; height: 54px; object-fit: contain; background: #fff; border-radius: 6px; padding: 4px; }
    .factor-brand strong { font-size: 21px; }
    .factor-body { padding: 30px 28px; }
    .factor-body h1 { color: #071b3f; font-size: 26px; margin-bottom: 10px; letter-spacing: 0; }
    .factor-body p { color: #5a687b; line-height: 1.6; }
    .factor-body label { color: #182b49; font-weight: 700; margin-bottom: 8px; }
    .factor-input { min-height: 54px; border: 1px solid #bdcadb; border-radius: 6px; font-size: 19px; letter-spacing: 0; text-align: center; }
    .factor-submit { min-height: 50px; width: 100%; border-radius: 6px; font-weight: 700; background: #145dcc; border-color: #145dcc; }
    .factor-cancel { display: block; text-align: center; margin-top: 18px; color: #52647d; }
</style>
@endpush

@section('content')
<main class="factor-page">
    <section class="factor-box" aria-labelledby="factor-title">
        <div class="factor-brand">
            <img src="{{ asset('/assets/img/saas-login-smat15.png') }}" alt="SmartProbook">
            <strong>SmartProbook</strong>
        </div>
        <div class="factor-body">
            <h1 id="factor-title">Verify it is you</h1>
            <p>Enter the six-digit code from your authenticator app. You can also use one of your recovery codes.</p>
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('two-factor.challenge.verify') }}">
                @csrf
                <label for="code">Authentication or recovery code</label>
                <input id="code" class="form-control factor-input" name="code" value="{{ old('code') }}" autocomplete="one-time-code" inputmode="text" maxlength="32" required autofocus>
                <button class="btn btn-primary factor-submit mt-3" type="submit"><i class="fas fa-shield-check me-1"></i> Verify and continue</button>
            </form>
            <a class="factor-cancel" href="{{ route('logout') }}">Cancel and return to sign in</a>
        </div>
    </section>
</main>
@endsection
