<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Verify your email - SmartProbook</title>
    <style>
        * { box-sizing: border-box; }
        html, body { min-height: 100%; margin: 0; background: #f4f7fb; font-family: Arial, sans-serif; }
        .verification-page { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: linear-gradient(145deg, #f7f9fc 0%, #edf3fb 100%); }
        .verification-box { width: min(100%, 460px); overflow: hidden; background: #fff; border: 1px solid #d8e1ed; border-radius: 8px; box-shadow: 0 22px 55px rgba(7, 27, 63, .12); }
        .verification-brand { padding: 22px 28px; color: #fff; background: #08275f; display: flex; align-items: center; gap: 12px; }
        .verification-brand img { width: 54px; height: 54px; object-fit: contain; background: #fff; border-radius: 6px; padding: 4px; }
        .verification-brand strong { font-size: 21px; }
        .verification-body { padding: 30px 28px; }
        .verification-body h1 { color: #071b3f; font-size: 26px; margin: 0 0 10px; }
        .verification-body p { color: #5a687b; line-height: 1.6; }
        .verification-body label { display: block; color: #182b49; font-weight: 700; margin-bottom: 8px; }
        .verification-input { width: 100%; min-height: 54px; border: 1px solid #bdcadb; border-radius: 6px; font-size: 25px; letter-spacing: 8px; text-align: center; }
        .verification-submit { min-height: 50px; width: 100%; border: 0; border-radius: 6px; font-weight: 700; color: #fff; background: #145dcc; cursor: pointer; margin-top: 12px; }
        .verification-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 18px; }
        .verification-actions button, .verification-actions a { padding: 0; border: 0; color: #145dcc; background: none; text-decoration: underline; cursor: pointer; font: inherit; }
        .verification-actions a { color: #5a687b; }
        .alert { padding: 12px 14px; margin: 14px 0; border-radius: 6px; line-height: 1.45; }
        .alert-success { color: #14532d; background: #dcfce7; }
        .alert-warning { color: #713f12; background: #fef3c7; }
        .alert-danger { color: #7f1d1d; background: #fee2e2; }
        @media (max-width: 480px) { .verification-body { padding: 24px 20px; } }
    </style>
</head>
<body>
<main class="verification-page">
    <section class="verification-box" aria-labelledby="verification-title">
        <div class="verification-brand">
            <img src="{{ asset('/assets/img/saas-login-smat15.png') }}" alt="SmartProbook">
            <strong>SmartProbook</strong>
        </div>
        <div class="verification-body">
            <h1 id="verification-title">Verify your email</h1>
            <p>We sent a six-digit code to <strong>{{ $email }}</strong>. Enter it to verify your email and continue. The code expires after 5 minutes; this page will not redirect automatically.</p>
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
                <input id="code" class="verification-input" name="code" value="{{ old('code') }}"
                    autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}"
                    maxlength="6" required autofocus>
                <button class="verification-submit" type="submit">Verify email and continue</button>
            </form>
            <div class="verification-actions">
                <form method="POST" action="{{ route('email-verification.resend') }}">
                    @csrf
                    <button type="submit">Resend code</button>
                </form>
                <a href="{{ route('logout') }}">Cancel and sign out</a>
            </div>
        </div>
    </section>
</main>
</body>
</html>
