<section class="block">
    <div class="block-head">
        <h2 class="kicker">Bill Planner</h2>
        @if ($showDetails)
            <button class="text-btn" type="button" data-open="bills">See Details</button>
        @endif
    </div>
    @forelse ($bills as $bill)
        @if ($limit === null || $loop->index < $limit)
            <article class="bill">
                <div class="badge" style="background: {{ $billColors[$loop->index % count($billColors)] }}">{{ $bill['initial'] }}</div>
                <div>
                    <b>{{ $bill['name'] }}</b>
                    <small>{{ $bill['due'] }}</small>
                </div>
                <div class="amount">
                    {{ $bill['amount'] }}
                    <span>{{ $bill['category'] }}</span>
                </div>
            </article>
        @endif
    @empty
        <p class="empty">No upcoming bills.</p>
    @endforelse
</section>
