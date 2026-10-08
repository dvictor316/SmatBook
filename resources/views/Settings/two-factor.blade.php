<?php $page = 'two-factor'; ?>
@extends('layout.mainlayout')

@push('styles')
<style>
    .security-shell { max-width: 920px; }
    .security-status { border: 1px solid #d7e0ec; border-left: 5px solid #0f3a8a; border-radius: 8px; padding: 20px; background: #f8fbff; }
    .security-status.is-enabled { border-left-color: #138a55; background: #f2fbf6; }
    .security-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 6px; font-weight: 700; font-size: 13px; color: #9b1c1c; background: #feecec; }
    .security-badge.is-enabled { color: #087443; background: #dff8e9; }
    .security-panel { border-top: 1px solid #dce4ef; margin-top: 24px; padding-top: 24px; }
    .security-panel h6 { color: #071b3f; font-size: 18px; margin-bottom: 8px; }
    .security-muted { color: #59677b; line-height: 1.6; }
    .security-qr { width: 220px; max-width: 100%; border: 1px solid #d7e0ec; background: #fff; padding: 10px; border-radius: 8px; }
    .manual-key { display: block; padding: 12px; color: #071b3f; background: #eef3f9; border: 1px solid #ccd8e7; border-radius: 6px; font-family: monospace; font-size: 16px; letter-spacing: 0; overflow-wrap: anywhere; }
    .recovery-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; padding: 16px; border: 1px solid #e0b341; background: #fffbeb; border-radius: 8px; }
    .recovery-grid code { color: #071b3f; font-size: 15px; }
    .security-form { max-width: 620px; }
    .security-form label { color: #182b49; font-weight: 600; margin-bottom: 7px; }
    .security-form .form-control { min-height: 48px; border-color: #cbd7e6; border-radius: 6px; }
    @media (max-width: 575px) { .recovery-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="row">
            <div class="col-xl-3 col-md-4">
                <div class="card"><div class="card-body">
                    <div class="page-header"><div class="content-page-header"><h5>Settings</h5></div></div>
                    @component('components.settings-menu') @endcomponent
                </div></div>
            </div>

            <div class="col-xl-9 col-md-8">
                <div class="card"><div class="card-body security-shell">
                    <div class="content-page-header mb-4"><div>
                        <h5 class="setting-menu mb-1">Two-Factor Authentication</h5>
                        <p class="security-muted mb-0">Protect this account with a time-based code from an authenticator app.</p>
                    </div></div>

                    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                    @if($errors->any())
                        <div class="alert alert-danger"><strong>Please correct the following:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif

                    <div class="security-status {{ $enabled ? 'is-enabled' : '' }}">
                        <span class="security-badge {{ $enabled ? 'is-enabled' : '' }}">
                            <i class="fas {{ $enabled ? 'fa-shield-circle-check' : 'fa-shield' }}"></i>
                            {{ $enabled ? 'Enabled' : 'Not enabled' }}
                        </span>
                        <p class="security-muted mt-3 mb-0">
                            @if($enabled)
                                Authenticator verification is required whenever this account signs in. {{ $remainingRecoveryCodes }} recovery {{ Str::plural('code', $remainingRecoveryCodes) }} remain.
                            @else
                                After setup, a password alone will no longer be enough to access this account.
                            @endif
                        </p>
                    </div>

                    @if(!empty($recoveryCodes))
                        <section class="security-panel" aria-labelledby="recovery-heading">
                            <h6 id="recovery-heading">Save your recovery codes now</h6>
                            <p class="security-muted">Each code works once. Keep them somewhere secure and separate from your computer. They will not be shown again.</p>
                            <div class="recovery-grid" id="recoveryCodes">@foreach($recoveryCodes as $recoveryCode)<code>{{ $recoveryCode }}</code>@endforeach</div>
                            <button type="button" class="btn btn-outline-primary mt-3" id="copyRecoveryCodes"><i class="fas fa-copy me-1"></i> Copy codes</button>
                        </section>
                    @endif

                    @if($showSetup)
                        <section class="security-panel" aria-labelledby="setup-heading">
                            <h6 id="setup-heading">Connect your authenticator</h6>
                            <p class="security-muted">Scan this QR code in your authenticator app. If scanning is unavailable, enter the setup key manually.</p>
                            <div class="row align-items-center g-4">
                                <div class="col-lg-auto"><img class="security-qr" src="{{ $qrCodeDataUri }}" alt="Authenticator setup QR code"></div>
                                <div class="col-lg">
                                    <label class="form-label">Manual setup key</label>
                                    <code class="manual-key">{{ $manualSecret }}</code>
                                    <form class="security-form mt-3" method="POST" action="{{ route('two-factor.confirm') }}">
                                        @csrf
                                        <label for="confirmation_code">Six-digit authentication code</label>
                                        <input id="confirmation_code" class="form-control" name="code" value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
                                        <button class="btn btn-primary mt-3" type="submit"><i class="fas fa-check-circle me-1"></i> Confirm and enable</button>
                                    </form>
                                </div>
                            </div>
                        </section>
                    @elseif(!$enabled)
                        <section class="security-panel" aria-labelledby="start-heading">
                            <h6 id="start-heading">Enable authenticator protection</h6>
                            <p class="security-muted">Confirm your password to generate a private setup key. Accounts created with social login can set a password through password reset first.</p>
                            <form class="security-form" method="POST" action="{{ route('two-factor.setup') }}">
                                @csrf
                                <label for="setup_password">Current password</label>
                                <input id="setup_password" class="form-control" type="password" name="current_password" autocomplete="current-password" required>
                                <button class="btn btn-primary mt-3" type="submit"><i class="fas fa-qrcode me-1"></i> Start setup</button>
                            </form>
                        </section>
                    @else
                        <section class="security-panel" aria-labelledby="recovery-management-heading">
                            <h6 id="recovery-management-heading">Replace recovery codes</h6>
                            <p class="security-muted">This invalidates every existing recovery code and creates a fresh set.</p>
                            <form class="security-form" method="POST" action="{{ route('two-factor.recovery-codes') }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6"><label for="recovery_password">Current password</label><input id="recovery_password" class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
                                    <div class="col-md-6"><label for="recovery_code">Authenticator or recovery code</label><input id="recovery_code" class="form-control" name="code" autocomplete="one-time-code" maxlength="32" required></div>
                                </div>
                                <button class="btn btn-outline-primary mt-3" type="submit"><i class="fas fa-rotate me-1"></i> Generate new codes</button>
                            </form>
                        </section>

                        <section class="security-panel" aria-labelledby="disable-heading">
                            <h6 id="disable-heading">Disable two-factor authentication</h6>
                            <p class="security-muted">This removes the authenticator key and all recovery codes from the account.</p>
                            <form class="security-form" method="POST" action="{{ route('two-factor.disable') }}" onsubmit="return confirm('Disable two-factor authentication for this account?')">
                                @csrf @method('DELETE')
                                <div class="row g-3">
                                    <div class="col-md-6"><label for="disable_password">Current password</label><input id="disable_password" class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
                                    <div class="col-md-6"><label for="disable_code">Authenticator or recovery code</label><input id="disable_code" class="form-control" name="code" autocomplete="one-time-code" maxlength="32" required></div>
                                </div>
                                <button class="btn btn-danger mt-3" type="submit"><i class="fas fa-shield-halved me-1"></i> Disable protection</button>
                            </form>
                        </section>
                    @endif
                </div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('copyRecoveryCodes')?.addEventListener('click', async function () {
    const codes = Array.from(document.querySelectorAll('#recoveryCodes code')).map((item) => item.textContent.trim()).join('\n');
    await navigator.clipboard.writeText(codes);
    this.innerHTML = '<i class="fas fa-check me-1"></i> Copied';
});
</script>
@endpush
