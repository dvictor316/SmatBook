<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Your workspace</title></head>
<body>
    <main style="max-width:36rem;margin:10vh auto;padding:2rem;font-family:system-ui">
        <h1>Your workspace</h1>
        <p>{{ session('error', 'Please ask your business owner to grant access to the pages you need.') }}</p>
        <p>Please contact your business owner if you need additional access.</p>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
    </main>
</body>
</html>
