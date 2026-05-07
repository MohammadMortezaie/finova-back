<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password</title>
    <style>
        :root { color-scheme: dark; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; --bg:#121212; --panel:#212121; --muted:#b3b3b3; --text:#f5f5f5; --accent:#1db954; --border:rgba(179,179,179,.18); }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; background:var(--bg); color:var(--text); display:grid; place-items:center; padding:24px; }
        main { width:min(100%,460px); background:var(--panel); border:1px solid var(--border); border-radius:20px; padding:24px; }
        h1 { margin:0 0 8px; font-size:30px; }
        p { margin:0 0 20px; color:var(--muted); line-height:1.5; }
        label { display:block; color:var(--muted); font-weight:700; margin-bottom:8px; }
        input { width:100%; border:1px solid var(--border); background:#181818; color:var(--text); border-radius:14px; padding:14px; margin-bottom:14px; }
        button { width:100%; border:0; background:var(--accent); color:#121212; font-weight:900; padding:14px; border-radius:14px; }
        .status { color:#9df2bd; background:rgba(29,185,84,.12); border:1px solid rgba(29,185,84,.35); border-radius:12px; padding:10px; margin-bottom:14px; }
        .error { color:#ff9a9a; background:rgba(255,107,107,.12); border:1px solid rgba(255,107,107,.35); border-radius:12px; padding:10px; margin-bottom:14px; }
    </style>
</head>
<body>
    <main>
        <h1>Set new password</h1>
        <p>Enter the reset code from your email and choose a new password.</p>
        @if (session('status')) <div class="status">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
        <form method="post" action="{{ route('password.reset') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">
            <label for="code">Reset code</label>
            <input id="code" name="code" inputmode="numeric" maxlength="6" value="{{ old('code') }}" required>
            <label for="password">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            <button type="submit">Update password</button>
        </form>
    </main>
</body>
</html>
