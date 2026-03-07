<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'System Notification' }}</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="max-width:680px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
        <div style="background:#1d4ed8;color:#fff;padding:18px 22px;">
            <h2 style="margin:0;font-size:19px;">{{ $title ?? 'System Notification' }}</h2>
        </div>
        <div style="padding:22px;">
            <p style="margin-top:0;">{{ $intro ?? 'A new system event was recorded.' }}</p>
            @if(!empty($details) && is_array($details))
                <table style="width:100%;border-collapse:collapse;">
                    @foreach($details as $label => $value)
                        <tr>
                            <td style="padding:8px 10px;border:1px solid #e2e8f0;background:#f8fafc;font-weight:600;width:35%;">{{ $label }}</td>
                            <td style="padding:8px 10px;border:1px solid #e2e8f0;">{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
            <p style="margin-bottom:0;margin-top:16px;">Regards,<br>{{ config('app.name', 'SmartProbook') }}</p>
        </div>
    </div>
</body>
</html>
