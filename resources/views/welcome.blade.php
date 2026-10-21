<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Money Management | Personal Finance Tracker</title>
    <meta name="description" content="Money Management helps you track expenses, income, subscriptions, receipts, and budgets in one private finance app.">
    <style>
        :root {
            color-scheme: dark;
            --bg: #121212;
            --panel: #1d1f1e;
            --panel-soft: #181a19;
            --text: #f5f5f5;
            --muted: #b8b8b8;
            --accent: #1db954;
            --accent-soft: rgba(29, 185, 84, .12);
            --border: rgba(255, 255, 255, .12);
            --shadow: rgba(0, 0, 0, .28);
            --serif: "Iowan Old Style", Palatino, "Palatino Linotype", Georgia, serif;
            --sans: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-family: var(--sans);
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            overflow-x: clip;
            background:
                radial-gradient(ellipse 90% 55% at 78% -8%, rgba(29, 185, 84, .16), transparent 58%),
                radial-gradient(ellipse 42% 36% at 8% 18%, rgba(29, 185, 84, .07), transparent 60%),
                var(--bg);
            color: var(--text);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: radial-gradient(ellipse at 50% 20%, #000 20%, transparent 72%);
            z-index: 0;
        }

        a { color: inherit; text-decoration: none; }

        a:focus-visible,
        .button:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 3px;
        }

        .wrap {
            width: min(1120px, 100%);
            margin: 0 auto;
            padding-inline: 22px;
        }

        header,
        main,
        footer { position: relative; z-index: 1; }

        header {
            border-bottom: 1px solid var(--border);
            background: rgba(18, 18, 18, .78);
            position: sticky;
            top: 0;
            z-index: 5;
            backdrop-filter: blur(18px);
        }

        header::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(29, 185, 84, .7), transparent);
        }

        nav {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .brand img {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            box-shadow: 0 0 0 1px var(--border);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .nav-links a:hover { color: var(--text); }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 999px;
            background: var(--accent);
            color: #07120b;
            font-weight: 800;
            letter-spacing: -.01em;
            box-shadow: 0 10px 28px rgba(29, 185, 84, .22);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 36px rgba(29, 185, 84, .28);
        }

        .hero-stage {
            position: relative;
            overflow: hidden;
        }

        .hero-stage::after {
            content: "";
            position: absolute;
            left: 8%;
            right: 8%;
            bottom: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(29, 185, 84, .55), transparent);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr);
            gap: 56px;
            align-items: center;
            min-height: min(820px, calc(100vh - 76px));
            padding: 72px 0 88px;
        }

        .eyebrow {
            width: fit-content;
            max-width: 100%;
            border: 1px solid rgba(29, 185, 84, .36);
            background: var(--accent-soft);
            color: #8cf0ad;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.4;
            margin-bottom: 22px;
        }

        h1 {
            margin: 0;
            max-width: min(100%, 8.4ch);
            font-family: var(--serif);
            font-size: clamp(64px, 8vw, 108px);
            font-weight: 500;
            line-height: .88;
            letter-spacing: -.03em;
        }

        .lead {
            margin: 28px 0 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.65;
            max-width: 540px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 32px;
        }

        .secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
            box-shadow: none;
        }

        .secondary:hover { box-shadow: none; background: rgba(255, 255, 255, .04); }

        .stage {
            position: relative;
            display: grid;
            justify-items: end;
            perspective: 1200px;
        }

        .stage::before {
            content: "";
            position: absolute;
            width: 340px;
            height: 340px;
            right: 24px;
            top: 40px;
            background: radial-gradient(circle, rgba(29, 185, 84, .34), transparent 68%);
            filter: blur(8px);
            pointer-events: none;
        }

        .phone {
            position: relative;
            background: #0e0f0f;
            border: 1px solid var(--border);
            border-radius: 36px;
            padding: 14px;
            box-shadow:
                0 40px 90px var(--shadow),
                0 0 0 1px rgba(29, 185, 84, .08),
                inset 0 1px 0 rgba(255, 255, 255, .08);
            width: min(100%, 390px);
            transform: rotateY(-8deg) rotateX(4deg) rotateZ(-1.5deg);
        }

        .screen {
            border-radius: 26px;
            border: 1px solid var(--border);
            background:
                linear-gradient(180deg, rgba(29, 185, 84, .08), transparent 120px),
                var(--panel);
            padding: 22px 20px 20px;
            min-height: 560px;
            display: grid;
            align-content: start;
            gap: 16px;
        }

        .screen-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .screen-head img {
            width: 44px;
            height: 44px;
            border-radius: 12px;
        }

        .screen-title strong {
            display: block;
            font-size: 16px;
            letter-spacing: -.02em;
        }

        .screen-title span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            margin-top: 3px;
        }

        .spark {
            width: 100%;
            height: 72px;
            display: block;
        }

        .metric {
            background: var(--panel-soft);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px 16px 14px;
        }

        .metric span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .metric strong {
            font-family: var(--serif);
            font-size: 34px;
            font-weight: 500;
            letter-spacing: -.03em;
            font-variant-numeric: tabular-nums;
        }

        .metric.quiet strong {
            font-family: var(--sans);
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -.02em;
        }

        .bars {
            display: grid;
            gap: 12px;
            padding-top: 4px;
        }

        .bar {
            display: grid;
            grid-template-columns: 86px 1fr 48px;
            gap: 10px;
            align-items: center;
            color: var(--muted);
            font-size: 13px;
        }

        .track {
            height: 6px;
            background: rgba(255, 255, 255, .08);
            border-radius: 999px;
            overflow: hidden;
        }

        .fill {
            height: 100%;
            background: linear-gradient(90deg, rgba(29, 185, 84, .45), var(--accent));
            border-radius: inherit;
        }

        section { padding: 96px 0; }

        #features { scroll-margin-top: 88px; }

        .section-head {
            display: grid;
            grid-template-columns: minmax(0, .9fr) minmax(260px, .7fr);
            gap: 32px;
            align-items: end;
            margin-bottom: 36px;
            padding-bottom: 28px;
            border-bottom: 1px solid var(--border);
        }

        h2 {
            margin: 0;
            font-family: var(--serif);
            font-size: clamp(40px, 5vw, 64px);
            font-weight: 500;
            line-height: .95;
            letter-spacing: -.03em;
        }

        .section-copy {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            font-size: 16px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 14px;
        }

        .feature {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(255, 255, 255, .025), transparent 42%), var(--panel);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 24px;
            min-height: 210px;
            display: flex;
            flex-direction: column;
            grid-column: span 4;
            transition: border-color .2s ease, transform .2s ease;
        }

        .feature:nth-child(1) { grid-column: span 7; min-height: 280px; }
        .feature:nth-child(2) { grid-column: span 5; }
        .feature:nth-child(6) { grid-column: span 12; min-height: 0; }

        .feature::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 2px;
            background: transparent;
            transition: background .2s ease;
        }

        .feature:hover {
            transform: translateY(-2px);
            border-color: rgba(29, 185, 84, .4);
        }

        .feature:hover::before { background: var(--accent); }

        .feature-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: var(--accent-soft);
            color: var(--accent);
        }

        .feature-icon svg { width: 22px; height: 22px; }

        .feature-index {
            color: rgba(245, 245, 245, .28);
            font-family: var(--serif);
            font-size: 28px;
            letter-spacing: -.03em;
        }

        .feature h3 {
            margin: 0 0 10px;
            font-size: 20px;
            letter-spacing: -.02em;
        }

        .feature p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
            max-width: 46ch;
        }

        .feature-chart {
            margin-top: auto;
            padding-top: 28px;
            display: flex;
            align-items: end;
            gap: 8px;
            height: 72px;
        }

        .feature-chart span {
            display: block;
            flex: 1;
            border-radius: 6px 6px 2px 2px;
            background: rgba(29, 185, 84, .28);
        }

        .feature-chart span:nth-child(odd) { background: var(--accent); }
        .feature-chart span:nth-child(1) { height: 38%; }
        .feature-chart span:nth-child(2) { height: 62%; }
        .feature-chart span:nth-child(3) { height: 48%; }
        .feature-chart span:nth-child(4) { height: 84%; }
        .feature-chart span:nth-child(5) { height: 56%; }
        .feature-chart span:nth-child(6) { height: 96%; }
        .feature-chart span:nth-child(7) { height: 70%; }

        .feature:nth-child(6) {
            flex-direction: row;
            align-items: center;
            gap: 22px;
            padding: 22px 24px;
        }

        .feature:nth-child(6) .feature-top { margin: 0; }
        .feature:nth-child(6) .feature-index { display: none; }
        .feature:nth-child(6) .feature-copy { max-width: 760px; }

        .finale {
            position: relative;
            padding: 40px 0 110px;
        }

        .privacy-band {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) auto;
            gap: 36px;
            align-items: end;
            min-height: 420px;
            background:
                radial-gradient(ellipse 70% 80% at 80% 120%, rgba(29, 185, 84, .28), transparent 60%),
                var(--panel);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 56px 48px;
            box-shadow: 0 30px 80px var(--shadow);
        }

        .privacy-band h2 { max-width: 10ch; }

        .privacy-band p {
            color: var(--muted);
            margin: 22px 0 0;
            line-height: 1.7;
            max-width: 560px;
            font-size: 17px;
        }

        .privacy-band .hero-actions { margin-top: 0; justify-content: flex-end; }

        footer {
            border-top: 1px solid var(--border);
            padding: 28px 0 36px;
            color: var(--muted);
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 14px;
        }

        .footer-links {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        .footer-links a:hover { color: var(--text); }

        @media (max-width: 980px) {
            .feature:nth-child(1),
            .feature:nth-child(2),
            .feature:nth-child(3),
            .feature:nth-child(4),
            .feature:nth-child(5),
            .feature:nth-child(6) { grid-column: span 6; }

            .feature:nth-child(6) {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 860px) {
            .hero {
                grid-template-columns: 1fr;
                min-height: auto;
                gap: 36px;
            }

            .stage { justify-items: center; }

            .phone { transform: none; }

            .section-head,
            .privacy-band {
                grid-template-columns: 1fr;
            }

            .privacy-band { min-height: 0; padding: 32px 24px; }
            .privacy-band .hero-actions { justify-content: flex-start; }
            .privacy-band h2 { max-width: none; }
        }

        @media (max-width: 680px) {
            .wrap { width: 100%; padding-inline: 18px; }
            nav { align-items: flex-start; flex-direction: column; padding: 14px 0; }
            .nav-links { width: 100%; overflow-x: auto; padding-bottom: 2px; gap: 16px; }
            .hero { padding: 36px 0 48px; min-height: 0; }
            .hero > div,
            .stage,
            .phone,
            .feature { min-width: 0; max-width: 100%; }
            h1 { font-size: clamp(44px, 12vw, 56px); max-width: 100%; }
            h2 { font-size: clamp(34px, 10vw, 44px); }
            .lead { font-size: 16px; }
            section { padding: 56px 0; }
            .screen { min-height: 0; }
            .bar { grid-template-columns: 78px minmax(0, 1fr) 44px; }
            .feature,
            .feature:nth-child(1),
            .feature:nth-child(2),
            .feature:nth-child(3),
            .feature:nth-child(4),
            .feature:nth-child(5),
            .feature:nth-child(6) { grid-column: span 12; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .button, .feature { transition: none; }
        }
    </style>
</head>
<body>
    <header>
        <nav class="wrap" aria-label="Primary navigation">
            <a class="brand" href="{{ url('/') }}">
                <img src="{{ asset('images/money-management-icon.png') }}" alt="Money Management app icon">
                <span>Money Management</span>
            </a>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="{{ route('legal.privacy') }}">Privacy</a>
                <a href="{{ route('legal.terms') }}">Terms</a>
                <a href="mailto:info@webpulse.ca">Contact</a>
                @auth
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Log in</a>
                @endauth
            </div>
        </nav>
    </header>

    <main>
        <div class="hero-stage">
            <div class="wrap hero">
                <div>
                    <div class="eyebrow">Expense, income, receipt, and subscription tracking</div>
                    <h1>Money Management</h1>
                    <p class="lead">A private finance app for organizing daily spending, income, subscriptions, and receipt records without turning your personal budget into a spreadsheet project.</p>
                    <div class="hero-actions">
                        <a class="button" href="mailto:info@webpulse.ca">Contact support</a>
                        <a class="button secondary" href="{{ route('legal.privacy') }}">Read privacy policy</a>
                    </div>
                </div>

                <div class="stage">
                    <div class="phone" aria-label="Money Management app preview">
                        <div class="screen">
                            <div class="screen-head">
                                <div class="screen-title">
                                    <strong>Monthly overview</strong>
                                    <span>Organized financial activity</span>
                                </div>
                                <img src="{{ asset('images/money-management-icon.png') }}" alt="">
                            </div>
                            <svg class="spark" viewBox="0 0 320 72" aria-hidden="true">
                                <polyline fill="none" stroke="rgba(29,185,84,.35)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" points="4,54 48,48 92,40 136,44 180,28 224,32 268,16 316,20"></polyline>
                                <polyline fill="none" stroke="#1db954" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="4,54 48,48 92,40 136,44 180,28 224,32 268,16 316,20"></polyline>
                            </svg>
                            <div class="metric">
                                <span>Tracked balance</span>
                                <strong>$4,280</strong>
                            </div>
                            <div class="metric quiet">
                                <span>Upcoming subscriptions</span>
                                <strong>9:00 AM reminders</strong>
                            </div>
                            <div class="bars">
                                <div class="bar">
                                    <span>Groceries</span>
                                    <div class="track"><div class="fill" style="width: 74%"></div></div>
                                    <span>$640</span>
                                </div>
                                <div class="bar">
                                    <span>Rent</span>
                                    <div class="track"><div class="fill" style="width: 92%"></div></div>
                                    <span>$1.8k</span>
                                </div>
                                <div class="bar">
                                    <span>Income</span>
                                    <div class="track"><div class="fill" style="width: 86%"></div></div>
                                    <span>$5.4k</span>
                                </div>
                                <div class="bar">
                                    <span>Receipts</span>
                                    <div class="track"><div class="fill" style="width: 58%"></div></div>
                                    <span>42</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section id="features">
            <div class="wrap">
                <div class="section-head">
                    <h2>Built for everyday personal finance</h2>
                    <p class="section-copy">Money Management keeps the core workflow simple: record what came in, what went out, what is renewing soon, and where your receipt proof lives.</p>
                </div>

                <div class="features">
                    <article class="feature">
                        <div class="feature-top">
                            <div class="feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 15l3.5-3.5 2.5 2L19 8"/><path d="M15 8h4v4"/></svg>
                            </div>
                            <span class="feature-index" aria-hidden="true">01</span>
                        </div>
                        <h3>Expense and income tracking</h3>
                        <p>Log transactions, organize categories, and review your financial activity from a clear monthly view.</p>
                        <div class="feature-chart" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
                    </article>
                    <article class="feature">
                        <div class="feature-top">
                            <div class="feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v6h6"/><path d="M9 13h6"/><path d="M9 17h4"/></svg>
                            </div>
                            <span class="feature-index" aria-hidden="true">02</span>
                        </div>
                        <h3>Receipt capture</h3>
                        <p>Use the camera or photo library to attach receipts to your records and keep important purchase details available.</p>
                    </article>
                    <article class="feature">
                        <div class="feature-top">
                            <div class="feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v5l3 2"/><circle cx="12" cy="12" r="8"/><path d="M5 3.5 3.5 6"/><path d="M19 3.5 20.5 6"/></svg>
                            </div>
                            <span class="feature-index" aria-hidden="true">03</span>
                        </div>
                        <h3>Subscription reminders</h3>
                        <p>Track recurring subscriptions and receive one daily reminder in the morning when renewals are coming up.</p>
                    </article>
                    <article class="feature">
                        <div class="feature-top">
                            <div class="feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 7v10"/><path d="M15 9.5c0-1.2-1.2-2-3-2s-3 .7-3 2 1.1 1.8 3 2.2 3 1 3 2.3-1.2 2-3 2-3-.8-3-2"/></svg>
                            </div>
                            <span class="feature-index" aria-hidden="true">04</span>
                        </div>
                        <h3>Currency preferences</h3>
                        <p>Set your preferred currency and language during account setup for a more useful personal finance workspace.</p>
                    </article>
                    <article class="feature">
                        <div class="feature-top">
                            <div class="feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="m8 11 4 4 4-4"/><path d="M5 19h14"/></svg>
                            </div>
                            <span class="feature-index" aria-hidden="true">05</span>
                        </div>
                        <h3>Exports</h3>
                        <p>Export records when you need your own copy for review, reporting, or backup outside the app.</p>
                    </article>
                    <article class="feature">
                        <div class="feature-top">
                            <div class="feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v6c0 4.2 2.8 7.2 7 8.5 4.2-1.3 7-4.3 7-8.5V6l-7-3z"/><path d="m9 12 2 2 4-4"/></svg>
                            </div>
                            <span class="feature-index" aria-hidden="true">06</span>
                        </div>
                        <div class="feature-copy">
                            <h3>Privacy controls</h3>
                            <p>Review our privacy policy, terms, and account deletion instructions any time from this website.</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="finale">
            <div class="wrap">
                <div class="privacy-band">
                    <div>
                        <h2>Your account, your records</h2>
                        <p>Money Management stores personal finance information so the app can provide budgeting, receipt, subscription, and account features. You can request account deletion or contact support at info@webpulse.ca.</p>
                    </div>
                    <div class="hero-actions">
                        <a class="button" href="{{ route('legal.delete-account') }}">Delete account info</a>
                        <a class="button secondary" href="{{ route('legal.terms') }}">Terms</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="wrap footer-inner">
            <span>&copy; {{ date('Y') }} Money Management by Webpulse.</span>
            <div class="footer-links">
                <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
                <a href="{{ route('legal.terms') }}">Terms & Conditions</a>
                <a href="{{ route('legal.delete-account') }}">Delete Account</a>
                <a href="mailto:info@webpulse.ca">info@webpulse.ca</a>
            </div>
        </div>
    </footer>
</body>
</html>
