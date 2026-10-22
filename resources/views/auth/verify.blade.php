@extends('layouts.mobile-frame')

@section('title', 'Verify email | Finova')

@section('styles')
<style>
    .login {
        flex: 1;
        min-height: 0;
        overflow: auto;
        overscroll-behavior: contain;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 36px 24px 32px;
        -webkit-overflow-scrolling: touch;
    }
    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 28px;
        text-decoration: none;
        font-weight: 800;
        letter-spacing: -.02em;
    }
    .brand img { width: 46px; height: 46px; border-radius: 14px; }
    h1 { margin: 0 0 8px; font-size: 34px; letter-spacing: -.04em; }
    .lead { margin: 0 0 26px; color: rgba(244, 247, 242, .68); line-height: 1.45; }
    label {
        display: block;
        margin-bottom: 8px;
        color: rgba(244, 247, 242, .72);
        font-size: 13px;
        font-weight: 700;
    }
    input[type="email"],
    input[type="text"] {
        width: 100%;
        margin-bottom: 16px;
        padding: 14px 16px;
        border: 1px solid rgba(255, 255, 255, .1);
        border-radius: 16px;
        background: rgba(0, 0, 0, .28);
        color: #fff;
    }
    #code { letter-spacing: .28em; font-weight: 800; font-size: 22px; }
    button[type="submit"] {
        width: 100%;
        border: 0;
        border-radius: 16px;
        padding: 15px 16px;
        background: #c6e35a;
        color: #12210e;
        font-weight: 800;
    }
    .resend { margin-top: 14px; }
    .resend button {
        width: 100%;
        border: 0;
        border-radius: 16px;
        padding: 14px 16px;
        background: rgba(255, 255, 255, .08);
        color: #fff;
        font-weight: 700;
    }
    .error, .status {
        margin-bottom: 16px;
        padding: 12px 14px;
        border-radius: 14px;
        font-size: 14px;
    }
    .error {
        background: rgba(255, 120, 120, .12);
        border: 1px solid rgba(255, 140, 140, .35);
        color: #ffc1c1;
    }
    .status {
        background: rgba(198, 227, 90, .12);
        border: 1px solid rgba(198, 227, 90, .35);
        color: #e7f6bf;
    }
</style>
@endsection

@section('content')
    <div class="login">
        <a class="brand" href="{{ url('/') }}">
            <img src="{{ asset('images/money-management-icon.png') }}" alt="">
            <span>Finova</span>
        </a>
        <h1>Check your email</h1>
        <p class="lead">Enter the 6-digit code. Your account stays inactive until this is confirmed.</p>
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('register.verify') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">
            <label for="code">Verification code</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" autofocus>
            <button type="submit">Verify email</button>
        </form>
        <form class="resend" method="post" action="{{ route('register.verify.resend') }}">
            @csrf
            <input type="hidden" name="email" value="{{ old('email', $email) }}">
            <button type="submit">Send a new code</button>
        </form>
    </div>
@endsection
