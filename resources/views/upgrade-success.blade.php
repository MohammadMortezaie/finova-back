<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upgrade Successful</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #121212;
            --panel: #212121;
            --muted: #b3b3b3;
            --text: #f5f5f5;
            --accent: #1db954;
            --border: rgba(179, 179, 179, 0.18);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        main {
            width: min(100%, 500px);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }
        p {
            color: var(--muted);
            line-height: 1.5;
            margin: 0 0 22px;
        }
        a {
            display: block;
            background: var(--accent);
            color: #121212;
            text-decoration: none;
            text-align: center;
            font-weight: 900;
            padding: 15px;
            border-radius: 16px;
        }
    </style>
</head>
<body>
    <main>
        <h1>Upgrade successful</h1>
        <p>Thanks for upgrading. Your Pro access is active, and you now have access to more features.</p>
        <a href="{{ $returnUrl }}">Open app</a>
    </main>
</body>
</html>
