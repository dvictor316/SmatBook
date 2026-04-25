<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your password</title>
</head>
<body style="margin:0;padding:24px;background:#f3f7ff;font-family:Arial,sans-serif;color:#10284d;">
    <div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #dbe7ff;border-radius:16px;overflow:hidden;box-shadow:0 14px 34px rgba(37,99,235,0.08);">
        <div style="padding:20px 24px;background:linear-gradient(135deg,#2348c7 0%,#1b2fb5 38%,#0a148a 100%);color:#ffffff;">
            <h1 style="margin:0;font-size:22px;line-height:1.2;">Reset your password</h1>
            <p style="margin:8px 0 0;font-size:14px;line-height:1.6;color:rgba(255,255,255,0.88);">A secure password recovery request was made for your {{ \App\Models\Setting::mailFromName(config('app.name', 'Smartprobook Accounting')) }} account.</p>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Hello {{ $user->name ?: 'there' }},</p>
            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">Tap the button below to choose a new password. This recovery link will expire in {{ $expiresInMinutes ?? 60 }} minutes.</p>

            <div style="margin:24px 0;text-align:center;">
                <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 22px;background:#1d4ed8;color:#ffffff;text-decoration:none;font-weight:700;border-radius:12px;">Reset Password</a>
            </div>

            <p style="margin:0 0 10px;font-size:14px;line-height:1.7;">If the button does not open, copy and paste this link into your browser:</p>
            <p style="margin:0 0 18px;font-size:13px;line-height:1.7;word-break:break-all;color:#1d4ed8;">{{ $resetUrl }}</p>

            <div style="padding:14px 16px;background:#eef4ff;border:1px solid #dbe7ff;border-radius:12px;">
                <p style="margin:0;font-size:13px;line-height:1.7;color:#425a7f;">If you did not request this change, you can safely ignore this email. Your current password will stay the same.</p>
            </div>

            <p style="margin:18px 0 0;font-size:14px;line-height:1.7;">Regards,<br>{{ \App\Models\Setting::mailFromName(config('app.name', 'Smartprobook Accounting')) }}</p>
        </div>
    </div>
</body>
</html>
