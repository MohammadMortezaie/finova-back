<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Money Management')</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            background: #e6e6ea;
            color: #f4f7f2;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .shell {
            height: 100vh;
            display: grid;
            grid-template-columns: 1fr minmax(0, 430px) 1fr;
        }
        .side { min-width: 0; }
        .app {
            min-width: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background:
                radial-gradient(125% 36% at 50% -8%, rgba(214, 226, 96, .55), transparent 46%),
                radial-gradient(90% 28% at 50% 0%, rgba(52, 122, 68, .85), transparent 62%),
                linear-gradient(180deg, #1c4a32 0%, #10291c 34%, #07140e 100%);
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
            .shell { display: block; }
            .side { display: none; }
        }
    </style>
    @yield('styles')
</head>
<body class="@yield('body-class')">
    <div class="shell">
        <aside class="side side-left">@yield('side-left')</aside>
        <main class="app">
            @yield('content')
        </main>
        <aside class="side side-right">@yield('side-right')</aside>
    </div>
    @yield('scripts')
</body>
</html>
