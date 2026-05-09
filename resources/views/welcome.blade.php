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
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
        }

        a { color: inherit; text-decoration: none; }

        .wrap {
            width: min(1120px, calc(100% - 40px));
            margin: 0 auto;
        }

        header {
            border-bottom: 1px solid var(--border);
            background: rgba(18, 18, 18, .92);
            position: sticky;
            top: 0;
            z-index: 5;
            backdrop-filter: blur(18px);
        }

        nav {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .brand img {
            width: 42px;
            height: 42px;
            border-radius: 10px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 18px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .nav-links a:hover { color: var(--text); }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 8px;
            background: var(--accent);
            color: #07120b;
            font-weight: 900;
            box-shadow: 0 10px 28px rgba(29, 185, 84, .2);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr);
            gap: 40px;
            align-items: center;
            min-height: calc(100vh - 72px);
            padding: 58px 0 54px;
        }

        .eyebrow {
            width: fit-content;
            border: 1px solid rgba(29, 185, 84, .36);
            background: var(--accent-soft);
            color: #8cf0ad;
            border-radius: 999px;
            padding: 7px 11px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0;
            font-size: clamp(42px, 7vw, 82px);
            line-height: .98;
            letter-spacing: 0;
        }

        .lead {
            margin: 22px 0 0;
            color: var(--muted);
            font-size: 20px;
            line-height: 1.55;
            max-width: 620px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
            box-shadow: none;
        }

        .phone {
            background: #0e0f0f;
            border: 1px solid var(--border);
            border-radius: 32px;
            padding: 18px;
            box-shadow: 0 24px 70px var(--shadow);
            max-width: 390px;
            justify-self: end;
        }

        .screen {
            border-radius: 24px;
            border: 1px solid var(--border);
            background: var(--panel);
            padding: 20px;
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
            width: 48px;
            height: 48px;
            border-radius: 12px;
        }

        .screen-title strong { display: block; font-size: 18px; }
        .screen-title span { display: block; color: var(--muted); font-size: 13px; margin-top: 2px; }

        .metric {
            background: var(--panel-soft);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }

        .metric span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .metric strong {
            font-size: 28px;
            letter-spacing: 0;
        }

        .bars {
            display: grid;
            gap: 10px;
        }

        .bar {
            display: grid;
            grid-template-columns: 86px 1fr 46px;
            gap: 10px;
            align-items: center;
            color: var(--muted);
            font-size: 13px;
        }

        .track {
            height: 8px;
            background: rgba(255, 255, 255, .08);
            border-radius: 999px;
            overflow: hidden;
        }

        .fill {
            height: 100%;
            background: var(--accent);
            border-radius: inherit;
        }

        section {
            border-top: 1px solid var(--border);
            padding: 58px 0;
        }

        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 28px;
        }

        h2 {
            margin: 0;
            font-size: clamp(28px, 4vw, 44px);
            line-height: 1.05;
            letter-spacing: 0;
        }

        .section-copy {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
            max-width: 520px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .feature {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            min-height: 180px;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 900;
            margin-bottom: 18px;
        }

        .feature h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .feature p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .privacy-band {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: center;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
        }

        .privacy-band p {
            color: var(--muted);
            margin: 8px 0 0;
            line-height: 1.6;
        }

        footer {
            border-top: 1px solid var(--border);
            padding: 28px 0;
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
            gap: 16px;
            flex-wrap: wrap;
        }

        .footer-links a:hover { color: var(--text); }

        @media (max-width: 860px) {
            .hero {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .phone {
                justify-self: stretch;
                max-width: none;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .section-head,
            .privacy-band {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {
            .wrap { width: min(100% - 28px, 1120px); }
            nav { align-items: flex-start; flex-direction: column; padding: 14px 0; }
            .nav-links { width: 100%; overflow-x: auto; padding-bottom: 2px; }
            .hero { padding-top: 36px; }
            .screen { min-height: 480px; }
            .bar { grid-template-columns: 72px 1fr 40px; }
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
            </div>
        </nav>
    </header>

    <main>
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

            <div class="phone" aria-label="Money Management app preview">
                <div class="screen">
                    <div class="screen-head">
                        <div class="screen-title">
                            <strong>Monthly overview</strong>
                            <span>Organized financial activity</span>
                        </div>
                        <img src="{{ asset('images/money-management-icon.png') }}" alt="">
                    </div>
                    <div class="metric">
                        <span>Tracked balance</span>
                        <strong>$4,280</strong>
                    </div>
                    <div class="metric">
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

        <section id="features">
            <div class="wrap">
                <div class="section-head">
                    <h2>Built for everyday personal finance</h2>
                    <p class="section-copy">Money Management keeps the core workflow simple: record what came in, what went out, what is renewing soon, and where your receipt proof lives.</p>
                </div>

                <div class="features">
                    <article class="feature">
                        <div class="feature-icon">$</div>
                        <h3>Expense and income tracking</h3>
                        <p>Log transactions, organize categories, and review your financial activity from a clear monthly view.</p>
                    </article>
                    <article class="feature">
                        <div class="feature-icon">R</div>
                        <h3>Receipt capture</h3>
                        <p>Use the camera or photo library to attach receipts to your records and keep important purchase details available.</p>
                    </article>
                    <article class="feature">
                        <div class="feature-icon">9</div>
                        <h3>Subscription reminders</h3>
                        <p>Track recurring subscriptions and receive one daily reminder in the morning when renewals are coming up.</p>
                    </article>
                    <article class="feature">
                        <div class="feature-icon">C</div>
                        <h3>Currency preferences</h3>
                        <p>Set your preferred currency and language during account setup for a more useful personal finance workspace.</p>
                    </article>
                    <article class="feature">
                        <div class="feature-icon">E</div>
                        <h3>Exports</h3>
                        <p>Export records when you need your own copy for review, reporting, or backup outside the app.</p>
                    </article>
                    <article class="feature">
                        <div class="feature-icon">P</div>
                        <h3>Privacy controls</h3>
                        <p>Review our privacy policy, terms, and account deletion instructions any time from this website.</p>
                    </article>
                </div>
            </div>
        </section>

        <section>
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
