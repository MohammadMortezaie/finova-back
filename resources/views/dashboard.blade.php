@extends('layouts.mobile-frame')

@section('title', 'Dashboard | Money Management')

@section('styles')
<style>
    .headers, .tabbar { flex: none; }
    .headers, .screens { min-width: 0; }
    .screens {
        flex: 1;
        min-height: 0;
        overflow: auto;
        overscroll-behavior: contain;
        padding: 4px 18px 22px;
        -webkit-overflow-scrolling: touch;
    }
    .top {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 22px 18px 12px;
    }
    .top.is-active { display: flex; }
    .who { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: #214833;
        color: #f4f7f2;
        font-weight: 800;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, .16);
        flex: none;
    }
    .hello { color: rgba(244, 247, 242, .62); font-size: 13px; }
    .name { font-size: 18px; font-weight: 800; letter-spacing: -.02em; }
    .screen-title { margin: 0; font-size: 28px; letter-spacing: -.04em; }
    .ghosts { display: flex; gap: 8px; }
    .ghost {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: rgba(255, 255, 255, .08);
        color: rgba(255, 255, 255, .86);
    }
    .ghost svg { width: 18px; height: 18px; }
    .pills {
        display: flex;
        gap: 6px;
        margin: 4px 0 16px;
        overflow-x: auto;
    }
    .pills button {
        flex: 0 0 auto;
        border: 0;
        border-radius: 999px;
        padding: 9px 14px;
        background: transparent;
        color: rgba(244, 247, 242, .7);
        font-size: 13.5px;
        font-weight: 700;
    }
    .pills button.is-active {
        background: rgba(255, 255, 255, .14);
        color: #fff;
    }
    .panel { display: none; }
    .panel.is-active { display: block; }
    .screen { display: none; }
    .screen.is-active { display: block; }
    .card {
        padding: 16px 16px 14px;
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .035));
        border: 1px solid rgba(255, 255, 255, .08);
        box-shadow: 0 16px 40px rgba(0, 0, 0, .16);
    }
    .card + .card, .card + .block { margin-top: 16px; }
    .card-head, .block-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .kicker { margin: 0; font-size: 16px; font-weight: 800; }
    .muted { margin: 4px 0 0; color: rgba(244, 247, 242, .58); font-size: 13px; }
    .year {
        appearance: none;
        border: 0;
        border-radius: 999px;
        padding: 8px 30px 8px 12px;
        background: rgba(0, 0, 0, .28) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='none' stroke='%23ffffff' stroke-width='1.6' d='M1 1.5 6 6.5 11 1.5'/%3E%3C/svg%3E") no-repeat right 10px center;
        color: #fff;
        font-size: 13px;
        font-weight: 800;
    }
    .year option { background: #10291c; color: #fff; }
    .totals-head { margin-bottom: 12px; align-items: center; }
    .total-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 10px;
    }
    .total-box {
        grid-column: span 12;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-width: 0;
    }
    .total-box .kicker { font-size: 15px; color: rgba(244, 247, 242, .78); }
    .total-box strong {
        font-size: 32px;
        line-height: 1;
        letter-spacing: -.04em;
        overflow-wrap: anywhere;
    }
    .total-box.income { border-left: 4px solid #7ed06a; }
    .total-box.expense { border-left: 4px solid #e6c34a; }
    .chart-card { margin-top: 12px; }
    .bar-chart { width: 100%; height: 150px; display: block; }
    .chart-note { margin: 10px 0 0; }
    .figures {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 12px;
        margin-top: 18px;
    }
    .figures strong {
        font-size: 42px;
        line-height: .95;
        letter-spacing: -.045em;
        font-weight: 700;
    }
    .delta { text-align: right; font-size: 13px; font-weight: 800; color: #b6e37a; }
    .delta small { display: block; margin-top: 3px; font-weight: 600; color: #8fb56a; }
    .delta.is-down, .delta.is-down small { color: #ffb4b4; }
    .legend {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 16px 0 6px;
        color: rgba(244, 247, 242, .72);
        font-size: 12px;
        font-weight: 700;
    }
    .legend i {
        width: 18px;
        height: 3px;
        border-radius: 99px;
        background: #c6e35a;
    }
    .chart-row { display: grid; grid-template-columns: 36px minmax(0, 1fr); gap: 4px; }
    .y-axis {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 150px;
        color: rgba(244, 247, 242, .45);
        font-size: 11px;
    }
    .chart { width: 100%; height: 132px; display: block; }
    .months {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        margin-top: 6px;
        color: rgba(244, 247, 242, .62);
        font-size: 10px;
        font-weight: 700;
        text-align: center;
    }
    .months span { min-width: 0; }
    .months .is-now { color: #d6f27a; }
    .dots { display: flex; flex-wrap: wrap; gap: 10px 14px; margin: 14px 0 12px; }
    .dots span { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: rgba(244, 247, 242, .78); }
    .swatch { width: 8px; height: 8px; border-radius: 50%; }
    .track {
        display: flex;
        height: 12px;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(255, 255, 255, .08);
    }
    .track span { display: block; height: 100%; }
    .cats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 14px;
    }
    .cat {
        min-width: 0;
        padding: 12px 8px 14px;
        border-radius: 18px;
        background: rgba(8, 20, 14, .35);
        border: 1px solid rgba(255, 255, 255, .06);
        text-align: center;
    }
    .cat .mark {
        width: 34px;
        height: 34px;
        margin: 0 auto 8px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: rgba(255, 255, 255, .06);
    }
    .cat .mark svg { width: 18px; height: 18px; }
    .cat strong { display: block; font-size: 20px; letter-spacing: -.03em; }
    .cat em {
        display: block;
        margin-top: 4px;
        font-style: normal;
        font-size: 11px;
        line-height: 1.25;
        color: rgba(244, 247, 242, .62);
    }
    .block { margin-top: 22px; }
    .text-btn {
        border: 0;
        background: transparent;
        color: rgba(244, 247, 242, .78);
        font-size: 13px;
        font-weight: 700;
        padding: 0;
    }
    .bill {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 13px 0;
        border-top: 1px solid rgba(255, 255, 255, .07);
    }
    .bill:first-of-type { border-top: 0; }
    .badge {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-weight: 800;
        color: #10210e;
    }
    .bill b { display: block; font-size: 15px; }
    .bill small { color: rgba(244, 247, 242, .55); }
    .bill .amount { text-align: right; font-weight: 800; }
    .bill .amount span { display: block; margin-top: 3px; font-size: 12px; font-weight: 600; color: rgba(244, 247, 242, .5); }
    .empty { margin: 8px 0 0; color: rgba(244, 247, 242, .6); }
    .profile { text-align: center; padding: 28px 18px; }
    .profile .avatar { width: 72px; height: 72px; margin: 0 auto 14px; font-size: 28px; }
    .profile h2 { margin: 0; font-size: 26px; letter-spacing: -.03em; }
    .profile .email { margin: 6px 0 18px; color: rgba(244, 247, 242, .66); }
    .facts { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: left; }
    .facts div {
        padding: 12px;
        border-radius: 16px;
        background: rgba(0, 0, 0, .2);
    }
    .facts dt { color: rgba(244, 247, 242, .55); font-size: 12px; }
    .facts dd { margin: 4px 0 0; font-weight: 800; }
    .logout {
        width: 100%;
        margin-top: 16px;
        border: 0;
        border-radius: 16px;
        padding: 14px;
        background: rgba(255, 255, 255, .1);
        color: #fff;
        font-weight: 800;
    }
    .telegram { margin-top: 16px; text-align: left; }
    .telegram-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .telegram h3 { margin: 0; font-size: 16px; letter-spacing: -.02em; }
    .telegram p { margin: 10px 0 0; color: rgba(244, 247, 242, .66); font-size: 13px; line-height: 1.45; }
    .status {
        flex: none;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 800;
    }
    .status.is-off { background: rgba(255, 255, 255, .08); color: rgba(244, 247, 242, .72); }
    .status.is-pending { background: rgba(230, 195, 74, .16); color: #e6c34a; }
    .status.is-on { background: rgba(126, 208, 106, .16); color: #7ed06a; }
    .activate {
        display: block;
        width: 100%;
        margin-top: 14px;
        border: 0;
        border-radius: 16px;
        padding: 14px;
        background: #d6f27a;
        color: #10210e;
        font-weight: 800;
        text-align: center;
        text-decoration: none;
    }
    .dash-actions {
        display: grid;
        gap: 10px;
        margin-bottom: 14px;
    }
    .dash-actions button {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        border: 0;
        border-radius: 18px;
        padding: 16px;
        font-weight: 800;
        text-align: left;
    }
    .dash-actions svg { width: 18px; height: 18px; flex: none; }
    .dash-actions .upload-btn { background: #d6f27a; color: #10210e; }
    .dash-actions .history-btn {
        background: rgba(255, 255, 255, .08);
        color: #f4f7f2;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .1);
    }
    .back-link,
    .history-link {
        border: 0;
        background: transparent;
        color: #d6f27a;
        font-weight: 800;
        padding: 0;
    }
    .back-link { margin: 0 0 12px; }
    .history-link { margin-top: 12px; }
    .shortcut {
        position: fixed;
        inset: 0;
        z-index: 40;
        display: grid;
        place-items: end center;
        padding: 18px 18px calc(18px + env(safe-area-inset-bottom));
        background: rgba(0, 0, 0, .5);
    }
    .shortcut[hidden] { display: none; }
    .shortcut-card {
        width: min(100%, 360px);
        padding: 18px;
        border-radius: 22px;
        background: #163526;
        border: 1px solid rgba(255, 255, 255, .16);
        box-shadow: 0 18px 40px rgba(0, 0, 0, .28);
    }
    .shortcut-card h2 { margin: 0; font-size: 22px; letter-spacing: -.03em; }
    .shortcut-card p { margin: 8px 0 0; color: rgba(244, 247, 242, .74); line-height: 1.45; }
    .shortcut-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
    .shortcut-actions button {
        border: 0;
        border-radius: 14px;
        padding: 13px 12px;
        font-weight: 800;
    }
    .shortcut-no { background: rgba(255, 255, 255, .1); color: #fff; }
    .shortcut-yes { background: #d6f27a; color: #10210e; }
    .tabbar {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 4px;
        padding: 6px 8px calc(10px + env(safe-area-inset-bottom));
        background: linear-gradient(180deg, rgba(7, 20, 14, 0), rgba(7, 20, 14, .92) 28%);
    }
    .tabbar button {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        min-width: 0;
        border: 0;
        border-radius: 14px;
        padding: 8px 2px 6px;
        background: transparent;
        color: rgba(255, 255, 255, .62);
        font-size: 10px;
        font-weight: 800;
        line-height: 1;
    }
    .tabbar button svg { width: 20px; height: 20px; }
    .upload-error {
        margin-top: 12px;
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(255, 120, 120, .12);
        border: 1px solid rgba(255, 140, 140, .35);
        color: #ffc1c1;
        font-size: 14px;
    }
    .upload-actions {
        display: grid;
        gap: 10px;
        margin-top: 16px;
    }
    .upload-choice {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 52px;
        border-radius: 16px;
        font-weight: 800;
        cursor: pointer;
    }
    .upload-choice svg { width: 20px; height: 20px; flex: none; }
    .upload-choice.camera { background: #c6e35a; color: #12210e; }
    .upload-choice.library { background: rgba(255, 255, 255, .1); color: #fff; }
    .upload-choice input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
    .upload-status:empty { display: none; }
    .upload-status { text-align: center; margin-bottom: 0; }
    .file-link {
        display: inline-grid;
        place-items: center;
        width: 32px;
        height: 32px;
        margin-top: 6px;
        border-radius: 10px;
        background: rgba(214, 242, 122, .16);
        color: #d6f27a;
        text-decoration: none;
    }
    .file-link svg { width: 16px; height: 16px; display: block; }
    .tabbar button.is-active {
        background: rgba(198, 227, 90, .18);
        color: #d6f27a;
    }
</style>
@endsection

@section('content')
    @php
        $billColors = ['#7ed06a', '#e6c34a', '#5b93f5', '#ef8fb2'];
    @endphp

    <div class="headers">
        <div class="top is-active" data-top="home">
            <div class="who">
                <div class="avatar" aria-hidden="true">{{ $initial }}</div>
                <div>
                    <div class="hello">Welcome</div>
                    <div class="name">{{ $user->name }}</div>
                </div>
            </div>
            @include('dashboard.icons')
        </div>
        <div class="top" data-top="report">
            <h1 class="screen-title">Report</h1>
            @include('dashboard.icons')
        </div>
        <div class="top" data-top="profile">
            <h1 class="screen-title">Profile</h1>
            @include('dashboard.icons')
        </div>
        <div class="top" data-top="upload">
            <h1 class="screen-title">Upload</h1>
            @include('dashboard.icons')
        </div>
        <div class="top" data-top="records">
            <h1 class="screen-title">History</h1>
            @include('dashboard.icons')
        </div>
    </div>

    @php
        $openScreen = session('open_screen') ?: ($errors->has('file') ? 'upload' : null);
    @endphp
    <div class="screens" @if ($openScreen) data-open-screen="{{ $openScreen }}" @endif>
        <section class="screen is-active" data-screen="home">
            <div class="dash-actions">
                <button type="button" class="upload-btn" data-go="upload">
                    <span>Upload file</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V5"/><path d="M7.5 9.5 12 5l4.5 4.5"/><path d="M5 19h14"/></svg>
                </button>
                <button type="button" class="history-btn" data-go="records">
                    <span>Financial records history</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 7h11"/><path d="M8 12h11"/><path d="M8 17h11"/><path d="M4 7h.01"/><path d="M4 12h.01"/><path d="M4 17h.01"/></svg>
                </button>
            </div>
            <div class="pills" role="tablist" aria-label="Home">
                <button type="button" class="is-active" data-pill="savings" role="tab" aria-selected="true">Totals</button>
                <button type="button" data-pill="bills" role="tab" aria-selected="false">Bill Planner</button>
                <button type="button" data-pill="spending" role="tab" aria-selected="false">Spending</button>
            </div>

            <div class="panel is-active" data-home-panel="savings">
                <div class="card-head totals-head">
                    <p class="muted">{{ $rangeLabel }}</p>
                    <form method="get" action="{{ route('dashboard') }}">
                        <label class="visually-hidden" for="year" style="position:absolute;width:1px;height:1px;overflow:hidden;">Year</label>
                        <select class="year" id="year" name="year" onchange="this.form.submit()">
                            @foreach ($years as $option)
                                <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="total-grid">
                    <article class="card total-box income">
                        <h2 class="kicker">Total income</h2>
                        <strong>{{ $incomeLabel }}</strong>
                    </article>
                    <article class="card total-box expense">
                        <h2 class="kicker">Total expense</h2>
                        <strong>{{ $expenseLabel }}</strong>
                    </article>
                </div>
                <article class="card chart-card">
                    <div class="card-head">
                        <div>
                            <h2 class="kicker">Monthly activity</h2>
                            <p class="muted">Uploaded income and expenses</p>
                        </div>
                    </div>
                    <div class="dots">
                        <span><i class="swatch" style="background:#7ed06a"></i>Income</span>
                        <span><i class="swatch" style="background:#e6c34a"></i>Expense</span>
                    </div>
                    <div class="chart-row">
                        <div class="y-axis" aria-hidden="true">
                            @foreach ($chart['yLabels'] as $label)
                                <span>{{ $label }}</span>
                            @endforeach
                        </div>
                        <div>
                            <svg class="bar-chart" viewBox="0 0 312 128" role="img" aria-label="Monthly income and expenses for {{ $year }}">
                                <line x1="0" y1="8" x2="312" y2="8" stroke="rgba(255,255,255,.14)"/>
                                <line x1="0" y1="64" x2="312" y2="64" stroke="rgba(255,255,255,.1)"/>
                                <line x1="0" y1="120" x2="312" y2="120" stroke="rgba(255,255,255,.22)"/>
                                @foreach ($chart['bars'] as $index => $bar)
                                    @php
                                        $group = $index * 26;
                                        $incomeHeight = $bar['income'] > 0 ? max($bar['income'] * 1.12, 4) : 0;
                                        $expenseHeight = $bar['expense'] > 0 ? max($bar['expense'] * 1.12, 4) : 0;
                                    @endphp
                                    @if ($incomeHeight > 0)
                                        <rect x="{{ $group + 2 }}" y="{{ 120 - $incomeHeight }}" width="9" height="{{ $incomeHeight }}" rx="3" fill="#7ed06a">
                                            <title>{{ $bar['label'] }} income {{ number_format($bar['incomeAmount'], 0) }}</title>
                                        </rect>
                                    @endif
                                    @if ($expenseHeight > 0)
                                        <rect x="{{ $group + 13 }}" y="{{ 120 - $expenseHeight }}" width="9" height="{{ $expenseHeight }}" rx="3" fill="#e6c34a">
                                            <title>{{ $bar['label'] }} expense {{ number_format($bar['expenseAmount'], 0) }}</title>
                                        </rect>
                                    @endif
                                @endforeach
                            </svg>
                            <div class="months">
                                @foreach ($chart['bars'] as $bar)
                                    <span @class(['is-now' => $bar['current']])>{{ $bar['label'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @if ($chart['empty'])
                        <p class="muted chart-note">Upload a file to see this year by month.</p>
                    @endif
                </article>
            </div>

            <div class="panel" data-home-panel="bills">
                @include('dashboard.bills', ['showDetails' => false, 'limit' => null])
            </div>

            <div class="panel" data-home-panel="spending">
                @include('dashboard.footprint')
            </div>
        </section>

        <section class="screen" data-screen="report">
            @include('dashboard.footprint')
            @include('dashboard.bills', ['showDetails' => true, 'limit' => 4])
        </section>

        <section class="screen" data-screen="upload">
            @include('dashboard.upload')
        </section>

        <section class="screen" data-screen="records">
            @include('dashboard.records')
        </section>

        <section class="screen" data-screen="profile">
            <article class="card profile">
                <div class="avatar" aria-hidden="true">{{ $initial }}</div>
                <h2>{{ $user->name }}</h2>
                <p class="email">{{ $user->email }}</p>
                <dl class="facts">
                    <div>
                        <dt>Currency</dt>
                        <dd>{{ $currency }}</dd>
                    </div>
                    <div>
                        <dt>Plan</dt>
                        <dd>{{ $plan }}</dd>
                    </div>
                </dl>
                <section class="telegram">
                    @php
                        $telegramStatus = $user->telegramBotStatus();
                    @endphp
                    <div class="telegram-head">
                        <h3>Telegram bot</h3>
                        @if ($telegramStatus === 'activated')
                            <span class="status is-on">Activated</span>
                        @elseif ($telegramStatus === 'pending')
                            <span class="status is-pending">Pending</span>
                        @else
                            <span class="status is-off">Not activated</span>
                        @endif
                    </div>
                    @if ($telegramStatus === 'activated')
                        <p>Connected {{ $user->telegram_activated_at->format('M j, Y') }}.</p>
                    @elseif ($telegramStatus === 'pending')
                        <p>{{ session('telegram_notice', 'Activation is saved. The Telegram link will show here once the bot is connected.') }}</p>
                    @else
                        <p>Activate the bot to send receipts from Telegram.</p>
                    @endif
                    @if ($telegramStatus === 'inactive')
                        <form method="post" action="{{ route('dashboard.telegram.activate') }}">
                            @csrf
                            <button class="activate" type="submit">Activate bot</button>
                        </form>
                    @elseif ($telegramStatus === 'pending' && filled(config('services.telegram.bot_username')))
                        <a class="activate" href="https://t.me/{{ ltrim(config('services.telegram.bot_username'), '@') }}?start={{ $user->telegram_link_token }}">Open Telegram</a>
                    @endif
                </section>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout" type="submit">Log out</button>
                </form>
            </article>
        </section>
    </div>

    <nav class="tabbar" aria-label="Dashboard">
        <button type="button" class="is-active" data-tab="home" aria-label="Dashboard" aria-pressed="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z"/></svg>
            <span>Dashboard</span>
        </button>
        <button type="button" data-tab="records" aria-label="History" aria-pressed="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 7h11"/><path d="M8 12h11"/><path d="M8 17h11"/><path d="M4 7h.01"/><path d="M4 12h.01"/><path d="M4 17h.01"/></svg>
            <span>History</span>
        </button>
        <button type="button" data-tab="upload" aria-label="Upload" aria-pressed="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V5"/><path d="M7.5 9.5 12 5l4.5 4.5"/><path d="M5 19h14"/></svg>
            <span>Upload</span>
        </button>
        <button type="button" data-tab="report" aria-label="Report" aria-pressed="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-4"/><path d="M12 16V8"/><path d="M16 16v-6"/></svg>
            <span>Report</span>
        </button>
        <button type="button" data-tab="profile" aria-label="Profile" aria-pressed="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.2"/><path d="M5 19.2c1.4-3 3.8-4.4 7-4.4s5.6 1.4 7 4.4"/></svg>
            <span>Profile</span>
        </button>
    </nav>
    @if (session('offer_shortcut'))
        <div class="shortcut" data-shortcut hidden>
            <div class="shortcut-card" role="dialog" aria-modal="true" aria-labelledby="shortcut-title">
                <h2 id="shortcut-title">Add a shortcut?</h2>
                <p data-shortcut-copy>Add Money Management to your phone so you can open it like an app.</p>
                <div class="shortcut-actions" data-shortcut-actions>
                    <button class="shortcut-no" type="button" data-shortcut-no>Not now</button>
                    <button class="shortcut-yes" type="button" data-shortcut-yes>Yes</button>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
<script>
    const tops = document.querySelectorAll('[data-top]');
    const screens = document.querySelectorAll('[data-screen]');
    const tabs = document.querySelectorAll('[data-tab]');
    const pills = document.querySelectorAll('[data-pill]');
    const panels = document.querySelectorAll('[data-home-panel]');

    function setTab(name) {
        tabs.forEach((tab) => {
            const on = tab.dataset.tab === name;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function showScreen(name) {
        const header = name === 'spending' ? 'home' : name;
        const tab = name === 'spending' ? 'home' : name;
        tops.forEach((el) => el.classList.toggle('is-active', el.dataset.top === header));
        screens.forEach((el) => el.classList.toggle('is-active', el.dataset.screen === (name === 'spending' ? 'home' : name)));
        setTab(tab);
        document.querySelector('.screens').scrollTop = 0;
    }

    function showPanel(name) {
        pills.forEach((pill) => {
            const on = pill.dataset.pill === name;
            pill.classList.toggle('is-active', on);
            pill.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.homePanel === name));
        showScreen(name === 'spending' ? 'spending' : 'home');
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            if (tab.dataset.tab === 'home') {
                showPanel('savings');
                return;
            }
            showScreen(tab.dataset.tab);
        });
    });

    pills.forEach((pill) => {
        pill.addEventListener('click', () => showPanel(pill.dataset.pill));
    });

    document.querySelectorAll('[data-open]').forEach((button) => {
        button.addEventListener('click', () => showPanel(button.dataset.open));
    });

    document.querySelectorAll('[data-go]').forEach((button) => {
        button.addEventListener('click', () => showScreen(button.dataset.go));
    });

    const shortcut = document.querySelector('[data-shortcut]');
    if (shortcut) {
        const mobile = window.matchMedia('(max-width: 720px)').matches;
        const installed = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const copy = shortcut.querySelector('[data-shortcut-copy]');
        const actions = shortcut.querySelector('[data-shortcut-actions]');

        const closeShortcut = () => {
            shortcut.hidden = true;
        };

        const waitForInstall = () => new Promise((resolve) => {
            if (window.__installPrompt) {
                resolve(window.__installPrompt);
                return;
            }
            const timer = setTimeout(() => resolve(null), 1800);
            window.addEventListener('beforeinstallprompt', () => {
                clearTimeout(timer);
                resolve(window.__installPrompt);
            }, { once: true });
        });

        if (mobile && !installed) {
            shortcut.hidden = false;
        }

        shortcut.addEventListener('click', (event) => {
            if (event.target === shortcut) {
                closeShortcut();
            }
        });

        actions.addEventListener('click', async (event) => {
            const button = event.target.closest('button');
            if (!button) {
                return;
            }
            if (button.dataset.shortcutNo !== undefined && button.dataset.shortcutYes === undefined) {
                closeShortcut();
                return;
            }
            if (button.dataset.shortcutYes === undefined) {
                return;
            }

            const ready = window.__installPrompt;
            button.disabled = true;
            if (ready) {
                window.__installPrompt = null;
                ready.prompt();
                await ready.userChoice;
                closeShortcut();
                return;
            }

            const prompt = await waitForInstall();
            if (prompt) {
                prompt.prompt();
                await prompt.userChoice;
                window.__installPrompt = null;
                closeShortcut();
                return;
            }

            copy.textContent = ios
                ? 'Tap the Share button in Safari, then tap Add to Home Screen.'
                : 'Open the browser menu and choose Install app or Add to Home screen.';
            actions.innerHTML = '<button class="shortcut-yes" type="button" data-shortcut-no>OK</button>';
        });
    }

    const openScreen = document.querySelector('[data-open-screen]')?.dataset.openScreen;
    if (openScreen) {
        showScreen(openScreen);
    }

    document.querySelectorAll('[data-upload-form]').forEach((form) => {
        const input = form.querySelector('input[type="file"]');
        input?.addEventListener('change', () => {
            if (!input.files?.length) {
                return;
            }
            const status = document.querySelector('[data-upload-status]');
            if (status) {
                status.textContent = 'Reading file…';
            }
            document.querySelectorAll('.upload-choice').forEach((choice) => {
                choice.style.pointerEvents = 'none';
                choice.style.opacity = '.7';
            });
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });
</script>
@endsection
