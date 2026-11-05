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
        <p>Enter this six-digit code in SmartProbook to verify your email address and finish setting up your account.</p>
        <p style="margin:28px 0;padding:16px;text-align:center;background:#f4f7fb;border-radius:6px;color:#08275f;font-size:32px;font-weight:700;letter-spacing:8px;">{{ $code }}</p>
        <p>This code expires at {{ $expiresAt }} and can only be used once. If you did not create a SmartProbook account, you can ignore this email.</p>
        <p style="color:#5a687b;font-size:13px;">Do not share this verification code with anyone.</p>
    </div>
</body>
</html>
