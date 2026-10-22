<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Money Management')</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/money-management-icon.png') }}">
    <meta name="theme-color" content="#10291c">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Money Management">
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            margin: 0;
            color: #f4f7f2;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(125% 36% at 50% -8%, rgba(214, 226, 96, .55), transparent 46%),
                radial-gradient(90% 28% at 50% 0%, rgba(52, 122, 68, .85), transparent 62%),
                linear-gradient(180deg, #1c4a32 0%, #10291c 34%, #07140e 100%);
        }
        .shell {
            height: 100vh;
            height: 100dvh;
            display: grid;
            grid-template-columns: 1fr minmax(0, 430px) 1fr;
        }
        .side { position: relative; min-width: 0; overflow: hidden; }
        .app {
            min-width: 0;
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: transparent;
        }
        .motion { position: absolute; inset: 0; pointer-events: none; }
        .glow, .ring, .beam { position: absolute; display: block; }
        .glow { border-radius: 50%; filter: blur(2px); opacity: .75; }
        .glow-1 {
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(214, 242, 122, .55), rgba(214, 242, 122, 0) 68%);
            top: 12%;
            left: 18%;
            animation: drift 16s ease-in-out infinite alternate;
        }
        .glow-2 {
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(126, 208, 106, .4), rgba(126, 208, 106, 0) 70%);
            bottom: 18%;
            right: 12%;
            animation: drift 22s ease-in-out infinite alternate-reverse;
        }
        .ring {
            width: 220px;
            height: 220px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, .22);
            box-shadow: inset 0 0 24px rgba(214, 242, 122, .12), 0 0 30px rgba(214, 242, 122, .08);
            top: 38%;
            left: 50%;
            animation: spin 28s linear infinite;
        }
        .beam {
            width: 1px;
            height: 42%;
            background: linear-gradient(180deg, transparent, rgba(255, 255, 255, .55), transparent);
            left: 62%;
            animation: sweep 11s ease-in-out infinite;
        }
        .motion-right .glow-1 { animation-duration: 19s; left: auto; right: 16%; }
        .motion-right .glow-2 { animation-duration: 24s; right: auto; left: 10%; }
        .motion-right .ring { animation-duration: 34s; animation-direction: reverse; }
        .motion-right .beam { left: 34%; animation-delay: -4s; }
        @keyframes drift {
            from { transform: translate3d(0, 0, 0) scale(1); }
            to { transform: translate3d(28px, -36px, 0) scale(1.12); }
        }
        @keyframes spin {
            from { transform: translateX(-50%) rotate(0deg); }
            to { transform: translateX(-50%) rotate(360deg); }
        }
        @keyframes sweep {
            0% { top: -20%; opacity: 0; }
            20% { opacity: 1; }
            80% { opacity: 1; }
            100% { top: 88%; opacity: 0; }
        }
        @media (min-width: 721px) {
            .shell { padding: 16px 0; }
            .app {
                height: calc(100dvh - 32px);
                border-radius: 32px;
                border: 1px solid rgba(255, 255, 255, .38);
                background: linear-gradient(180deg, rgba(255, 255, 255, .1), rgba(255, 255, 255, .02) 18%, transparent 42%);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, .55),
                    inset 0 0 0 1px rgba(255, 255, 255, .08),
                    0 0 0 1px rgba(214, 242, 122, .14),
                    0 18px 50px rgba(0, 0, 0, .22);
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .glow, .ring, .beam { animation: none; }
        }
        button, input, select { font: inherit; }
        button { cursor: pointer; }
        a { color: inherit; }
        :focus-visible {
            outline: 2px solid #d6f27a;
            outline-offset: 2px;
        }
        @media (max-width: 720px) {
            body { background: #07140e; }
            .shell { display: block; height: 100dvh; padding: 0; overflow: hidden; }
            .side { display: none; }
            .app {
                position: fixed;
                inset: 0;
                width: 100%;
                height: 100vh;
                height: 100dvh;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                background:
                    radial-gradient(125% 36% at 50% -8%, rgba(214, 226, 96, .55), transparent 46%),
                    radial-gradient(90% 28% at 50% 0%, rgba(52, 122, 68, .85), transparent 62%),
                    linear-gradient(180deg, #1c4a32 0%, #10291c 34%, #07140e 100%);
            }
        }
    </style>
    @yield('styles')
</head>
<body class="@yield('body-class')">
    <div class="shell">
        <aside class="side side-left">
            @hasSection('side-left')
                @yield('side-left')
            @else
                <div class="motion" aria-hidden="true">
                    <span class="glow glow-1"></span>
                    <span class="glow glow-2"></span>
                    <span class="ring"></span>
                    <span class="beam"></span>
                </div>
            @endif
        </aside>
        <main class="app">
            @yield('content')
        </main>
        <aside class="side side-right">
            @hasSection('side-right')
                @yield('side-right')
            @else
                <div class="motion motion-right" aria-hidden="true">
                    <span class="glow glow-1"></span>
                    <span class="glow glow-2"></span>
                    <span class="ring"></span>
                    <span class="beam"></span>
                </div>
            @endif
        </aside>
    </div>
    <script>
        window.__installPrompt = null;
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            window.__installPrompt = event;
        });
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
    @yield('scripts')
</body>
</html>
