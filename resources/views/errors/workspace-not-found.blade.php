<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace Not Found — SmartProbook</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f4f7f6; }
        .box { text-align: center; max-width: 420px; padding: 40px 32px; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        h1 { font-size: 1.5rem; color: #1e293b; margin-bottom: 12px; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        a { display: inline-block; padding: 10px 24px; background: #2563eb; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Workspace Not Found</h1>
        <p>The workspace you are looking for does not exist or has not been set up yet. Please contact your administrator or log in to your account.</p>
        <a href="{{ config('app.url') }}/saas-login">Go to Login</a>
    </div>
</body>
</html>
