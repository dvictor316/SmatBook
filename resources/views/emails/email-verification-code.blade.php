<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#182b49;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:32px auto;padding:32px;background:#fff;border:1px solid #d8e1ed;border-radius:8px;">
        <h1 style="margin:0 0 16px;color:#08275f;font-size:24px;">Verify your email</h1>
        <p>Enter this six-digit code in SmartProbook to verify your email address and continue. @if($sameSession)Use the same browser where you started connecting your account.@else If you do not have the verification screen open, use the button below first.@endif</p>
        <p style="margin:24px 0;text-align:center;">
            <a href="{{ $verificationUrl }}" style="display:inline-block;padding:13px 22px;background:#145dcc;color:#fff;text-decoration:none;border-radius:6px;font-weight:700;">Open verification and setup</a>
        </p>
        <p style="margin:28px 0;padding:16px;text-align:center;background:#f4f7fb;border-radius:6px;color:#08275f;font-size:32px;font-weight:700;letter-spacing:8px;">{{ $code }}</p>
        <p>This code expires at {{ $expiresAt }} and can only be used once. @unless($sameSession)The verification link remains available for 24 hours so you can open this screen and request a new code if needed. @endunless If you did not request this code, you can ignore this email.</p>
        <p style="color:#5a687b;font-size:13px;">Do not share this verification code with anyone.</p>
    </div>
</body>
</html>
