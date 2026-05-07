<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upgrade to Pro</title>
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
            width: min(100%, 520px);
        }
        h1 {
            margin: 0 0 8px;
            font-size: 32px;
            line-height: 1.1;
        }
        p {
            color: var(--muted);
            line-height: 1.5;
            margin: 0 0 20px;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 22px;
            margin-top: 18px;
        }
        ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 12px;
        }
        li::before {
            content: "✓";
            color: var(--accent);
            font-weight: 800;
            margin-right: 10px;
        }
        form {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }
        button {
            width: 100%;
            border: 1px solid var(--border);
            background: #181818;
            color: var(--text);
            padding: 16px;
            border-radius: 16px;
            font-size: 17px;
            font-weight: 800;
            text-align: left;
        }
        button.selected {
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent) inset;
        }
        .price {
            color: var(--accent);
            display: block;
            margin-top: 4px;
        }
        .error {
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.12);
            border: 1px solid rgba(255, 107, 107, 0.35);
            border-radius: 14px;
            padding: 12px;
            margin-top: 16px;
        }
        .setup {
            color: #ffd166;
            background: rgba(255, 209, 102, 0.12);
            border: 1px solid rgba(255, 209, 102, 0.35);
            border-radius: 14px;
            padding: 12px;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <main>
        <h1>Upgrade to Pro</h1>
        <p>Unlock exports, AI receipt extraction, unlimited uploads, and cloud receipt storage.</p>

        <section class="panel">
            <ul>
                <li>CSV and PDF reports</li>
                <li>AI receipt extraction</li>
                <li>Unlimited receipt uploads</li>
                <li>Cloud receipt access across devices</li>
            </ul>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            @unless ($stripeReady)
                <div class="setup">
                    Stripe checkout is not configured yet. Add STRIPE_SECRET, STRIPE_WEEKLY_PRICE_ID, and STRIPE_MONTHLY_PRICE_ID on the Laravel server.
                </div>
            @endunless

            <form method="post" action="{{ route('upgrade.checkout') }}">
                @csrf
                <input type="hidden" name="email" value="{{ old('email', $email) }}">
                <input type="hidden" name="return_url" value="{{ old('return_url', $returnUrl) }}">

                @foreach ($plans as $key => $plan)
                    <button type="submit" name="plan" value="{{ $key }}" class="{{ $selectedPlan === $key ? 'selected' : '' }}">
                        {{ $plan['label'] }}
                        <span class="price">{{ $plan['price'] }}</span>
                    </button>
                @endforeach
            </form>
        </section>
    </main>
</body>
</html>
