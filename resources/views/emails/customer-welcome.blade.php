<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmatBook Credentials</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="max-width:640px;margin:24px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <div style="padding:20px 24px;background:#1e40af;color:#ffffff;">
            <h2 style="margin:0;font-size:20px;">Your SmatBook Login Credentials</h2>
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Hello {{ $name ?? 'User' }},</p>
            <p>Your account has been created for <strong>{{ $companyName ?? 'your workspace' }}</strong>.</p>
            <p><strong>Email:</strong> {{ $email ?? 'N/A' }}<br>
               <strong>Temporary Password:</strong> {{ $password ?? 'N/A' }}</p>
            <p><strong>Login / Workspace URL:</strong> <a href="{{ $workspaceUrl ?? '#' }}">{{ $workspaceUrl ?? 'Unavailable' }}</a></p>
            <p style="margin-bottom:0;">Please log in and change your password immediately.</p>
        </div>
    </div>
</body>
</html>
