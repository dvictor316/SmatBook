<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'SmartProbook')</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            place-items: center;
            color: #0b1f44;
            background: #f4f7fb;
        }
        .error-shell {
            width: min(100%, 680px);
            padding: clamp(28px, 6vw, 56px);
            text-align: center;
            background: #fff;
            border: 1px solid #d8e2f0;
            border-top: 5px solid #1d5fd1;
            border-radius: 8px;
            box-shadow: 0 18px 50px rgba(15, 42, 86, .12);
        }
        .error-code {
            margin: 0 0 10px;
            color: #c62828;
            font-size: .8rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0 0 14px;
            font-size: clamp(1.65rem, 5vw, 2.35rem);
            line-height: 1.15;
        }
        p {
            margin: 0 auto 24px;
            max-width: 540px;
            color: #5b6b82;
            font-size: 1rem;
            line-height: 1.65;
            overflow-wrap: anywhere;
        }
        .error-detail {
            padding: 12px 14px;
            color: #7f1d1d;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 6px;
        }
        .error-action {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            color: #fff;
            background: #1d5fd1;
            border-radius: 6px;
            font-weight: 700;
            text-decoration: none;
        }
        .error-action:hover,
        .error-action:focus-visible { background: #164aa6; }
        @media (max-width: 480px) {
            body { padding: 14px; }
            .error-shell { padding: 28px 20px; }
            .error-action { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="error-shell" role="main">
        @yield('content')
    </main>
</body>
</html>
