@extends('layouts.mobile-frame')

@section('title', 'Log in | Money Management')

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
    .brand img {
        width: 46px;
        height: 46px;
        border-radius: 14px;
    }
    h1 {
        margin: 0 0 8px;
        font-size: 34px;
        letter-spacing: -.04em;
    }
    .lead {
        margin: 0 0 26px;
        color: rgba(244, 247, 242, .68);
        line-height: 1.45;
    }
    label {
        display: block;
        margin-bottom: 8px;
        color: rgba(244, 247, 242, .72);
        font-size: 13px;
        font-weight: 700;
    }
    input[type="email"],
    input[type="password"] {
        width: 100%;
        margin-bottom: 16px;
        padding: 14px 16px;
        border: 1px solid rgba(255, 255, 255, .1);
        border-radius: 16px;
        background: rgba(0, 0, 0, .28);
        color: #fff;
    }
    .row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 2px 0 18px;
        font-size: 14px;
    }
    .remember {
        display: flex;
        align-items: center;
        gap: 8px;
        color: rgba(244, 247, 242, .78);
    }
    .remember input { accent-color: #c6e35a; }
    .forgot { color: #d6f27a; font-weight: 700; text-decoration: none; }
    button[type="submit"] {
        width: 100%;
        border: 0;
        border-radius: 16px;
        padding: 15px 16px;
        background: #c6e35a;
        color: #12210e;
        font-weight: 800;
    }
    button[type="submit"]:hover { filter: brightness(1.05); }
    .error {
        margin-bottom: 16px;
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(255, 120, 120, .12);
        border: 1px solid rgba(255, 140, 140, .35);
        color: #ffc1c1;
        font-size: 14px;
    }
</style>
@endsection

@section('content')
    <div class="login">
        <a class="brand" href="{{ url('/') }}">
            <img src="{{ asset('images/money-management-icon.png') }}" alt="">
            <span>Money Management</span>
        </a>
        <h1>Welcome back</h1>
        <p class="lead">Log in to see your savings, spending, and upcoming bills.</p>
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <div class="row">
                <label class="remember" for="remember">
                    <input id="remember" name="remember" type="checkbox" value="1">
                    Remember me
                </label>
                <a class="forgot" href="{{ route('password.forgot') }}">Forgot password</a>
            </div>
            <button type="submit">Log in</button>
        </form>
    </div>
@endsection
