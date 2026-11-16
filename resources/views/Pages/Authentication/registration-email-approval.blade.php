<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Confirm your email - SmartProbook</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f4f7fb;color:#182b49;padding:24px;">
    <main style="max-width:480px;margin:40px auto;background:#fff;padding:28px;border-radius:10px;">
        <h1>Confirm your email</h1>
        <p>Enter the six-digit code from your email to approve using this address for a SmartProbook business registration. The code is valid for five minutes.</p>
        @error('code') <p role="alert" style="color:#b00020;">{{ $message }}</p> @enderror
        <form method="POST" action="{{ route('registration-verification.verify', ['token' => $token]) }}">
            @csrf
            <label for="code">Verification code</label>
            <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required style="display:block;font-size:24px;padding:10px;margin:12px 0;">
            <button type="submit" style="padding:12px 20px;background:#145dcc;color:white;border:0;border-radius:6px;">Confirm email</button>
        </form>
        <p>Do not share your code. If you did not request this registration, close this page.</p>
    </main>
</body>
</html>
