<article class="card">
    <h2 class="kicker">Spending Footprint</h2>
    <p class="muted">{{ $monthRangeLabel }}</p>
    <div class="figures">
        <div>
            <p class="muted">Total Spendings</p>
            <strong>{{ $spendLabel }}</strong>
        </div>
    </div>
    <div class="dots">
        @foreach ($slices as $slice)
            <span><i class="swatch" style="background: {{ $slice['color'] }}"></i>{{ $slice['label'] }}</span>
        @endforeach
    </div>
    <div class="track" aria-hidden="true">
        @foreach ($slices as $slice)
            @if ($slice['percent'] > 0)
                <span style="width: {{ $slice['percent'] }}%; background: {{ $slice['color'] }}"></span>
            @endif
        @endforeach
    </div>
    <div class="cats">
        @foreach ($slices as $slice)
            <div class="cat">
                <div class="mark" style="color: {{ $slice['color'] }}">
                    @if ($slice['key'] === 'essentials')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3v6a2 2 0 0 0 4 0V3"/><path d="M8 6h4"/><path d="M10 11v10"/></svg>
                    @elseif ($slice['key'] === 'treat')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg>
                    @endif
                </div>
                <strong>{{ $slice['percent'] }}%</strong>
                <em>{{ $slice['label'] }}</em>
            </div>
        @endforeach
    </div>
</article>
