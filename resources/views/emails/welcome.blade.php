<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartProbook Workspace Ready</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="max-width:640px;margin:24px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <div style="padding:20px 24px;background:#0f172a;color:#ffffff;">
            <h2 style="margin:0;font-size:20px;">Your SmartProbook Workspace Is Ready</h2>
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Hi {{ $userName ?? 'there' }},</p>
            <p>Your subscription has been activated successfully.</p>
            <p><strong>Plan:</strong> {{ $planName ?? 'Active Plan' }}</p>
            <p><strong>Workspace URL:</strong> <a href="{{ $workspaceUrl ?? '#' }}">{{ $workspaceUrl ?? 'Unavailable' }}</a></p>
            <p style="margin-bottom:0;">Thank you for choosing SmartProbook.</p>
        </div>
    </div>
</body>
</html>
