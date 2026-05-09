<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delete Account | Money Management</title>
    <style>
        :root { color-scheme: dark; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; --bg:#121212; --panel:#212121; --muted:#b3b3b3; --text:#f5f5f5; --accent:#1db954; --border:rgba(179,179,179,.18); }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; background:var(--bg); color:var(--text); padding:28px; }
        main { width:min(100%,920px); margin:0 auto; background:var(--panel); border:1px solid var(--border); border-radius:20px; padding:28px; }
        h1 { margin:0 0 8px; font-size:34px; }
        h2 { margin:28px 0 10px; font-size:20px; }
        p, li { color:var(--muted); line-height:1.7; }
        a { color:var(--accent); font-weight:800; }
        .meta { color:var(--muted); margin-bottom:24px; }
        .notice { border:1px solid rgba(29,185,84,.35); background:rgba(29,185,84,.1); border-radius:14px; padding:14px; }
    </style>
</head>
<body>
    <main>
        <h1>Delete Account</h1>
        <p class="meta">Effective date: May 8, 2026</p>

        <p class="notice">This page explains how Money Management users can request deletion of their account and associated app data.</p>

        <h2>How to Request Account Deletion</h2>
        <p>To request deletion of your Money Management account, contact us using the contact information published on our website and include the email address associated with your account.</p>
        <p>For security, we may ask you to verify ownership of the account before deletion is completed.</p>

        <h2>What We Delete</h2>
        <p>After your deletion request is verified, we will delete or anonymize account data associated with your Money Management account, including:</p>
        <ul>
            <li>Account profile information, such as name, email address, language, and currency preferences.</li>
            <li>Expense records, income records, subscription records, and app settings.</li>
            <li>Receipt images and receipt analysis records associated with your account, where technically available.</li>
            <li>Authentication tokens and app access records tied to your account.</li>
        </ul>

        <h2>What May Be Retained</h2>
        <p>Some information may be retained when required for legal, security, billing, fraud-prevention, accounting, backup, dispute-resolution, or legitimate business purposes. This may include transaction records, server logs, support communications, payment provider records, and backup copies for a limited period.</p>
        <p>Backup copies are deleted on our normal backup rotation schedule and are not used to restore deleted accounts except where required for security, legal, or disaster recovery purposes.</p>

        <h2>Deletion Timeline</h2>
        <p>We aim to process verified account deletion requests within 30 days. Some retained backup or legal records may take longer to expire according to operational, legal, or security requirements.</p>

        <h2>Partial Data Deletion</h2>
        <p>If you want to delete individual expenses, income records, subscriptions, or receipt records without deleting your account, use the controls available inside the app where provided. If a specific record cannot be deleted in the app, contact us with your request.</p>

        <h2>Before You Delete</h2>
        <p>Account deletion may be permanent. You are responsible for exporting or saving any information you want to keep before requesting deletion. Money Management is not responsible for data that is deleted or becomes unavailable after an account deletion request is processed.</p>

        <p><a href="{{ route('legal.privacy') }}">View Privacy Policy</a> · <a href="{{ route('legal.terms') }}">View Terms & Conditions</a></p>
    </main>
</body>
</html>
