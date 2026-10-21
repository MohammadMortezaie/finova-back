<section class="block">
    <h2 class="kicker">Financial records history</h2>
    <p class="muted">Income and expenses saved from uploaded files.</p>
    @forelse ($records as $record)
        <article class="bill">
            <div class="badge" style="background: {{ $record['kind'] === 'Income' ? '#7ed06a' : '#e6c34a' }}">{{ $record['kind'] === 'Income' ? 'I' : 'E' }}</div>
            <div>
                <b>{{ $record['title'] }}</b>
                <small>{{ $record['date'] }} · {{ $record['detail'] }}</small>
            </div>
            <div class="amount">
                {{ $record['amount'] }}
                @if ($record['fileUrl'])
                    @include('dashboard.file-link', ['url' => $record['fileUrl']])
                @else
                    <span>{{ $record['kind'] }}</span>
                @endif
            </div>
        </article>
    @empty
        <p class="empty">No financial records yet.</p>
    @endforelse
</section>
