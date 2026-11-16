<!doctype html>
<html lang="en">
<body style="font-family:Arial,sans-serif;color:#182b49;max-width:560px;margin:32px auto;">
    <h1>Confirm your email</h1>
    <p>A SmartProbook business registration was started using your email address. Open the link below on your own device and enter your code to approve the registration.</p>
    <p><a href="{{ $verificationUrl }}">Open verification page</a></p>
    <p style="font-size:30px;font-weight:bold;letter-spacing:6px;">{{ $code }}</p>
    <p>The code and link expire after five minutes. Do not share the code with anyone. If you did not request this, ignore this email.</p>
</body>
</html>
