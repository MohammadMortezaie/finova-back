<article class="card">
    <h2 class="kicker">Upload a financial file</h2>
    <p class="muted">Take a photo with your camera, or choose a receipt, invoice, or photo you already have.</p>
    @if ($errors->has('file'))
        <div class="upload-error">{{ $errors->first('file') }}</div>
    @endif
    <div class="upload-actions">
        <form method="post" action="{{ route('dashboard.uploads.store') }}" enctype="multipart/form-data" data-upload-form>
            @csrf
            <label class="upload-choice camera">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 8h3l2-2h6l2 2h3v11H4V8Z"/><circle cx="12" cy="13" r="3.2"/></svg>
                <span>Take photo</span>
                <input name="file" type="file" accept="image/*" capture="environment">
            </label>
        </form>
        <form method="post" action="{{ route('dashboard.uploads.store') }}" enctype="multipart/form-data" data-upload-form>
            @csrf
            <label class="upload-choice library">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V5"/><path d="M7.5 9.5 12 5l4.5 4.5"/><path d="M5 15v5h14v-5"/></svg>
                <span>Choose file</span>
                <input name="file" type="file" accept="image/*,.pdf,.heic,.heif">
            </label>
        </form>
    </div>
    <p class="muted upload-status" data-upload-status></p>
</article>

@php $uploadResult = session('upload_result'); @endphp
@if (is_array($uploadResult))
    <article class="card">
        <p class="muted">Saved as {{ $uploadResult['kind'] }}</p>
        <h2 class="kicker">{{ $uploadResult['title'] }}</h2>
        <div class="figures">
            <strong>{{ $uploadResult['amount'] }}</strong>
        </div>
        <p class="muted">{{ $uploadResult['date'] }} · {{ $uploadResult['detail'] }}</p>
        @if (!empty($uploadResult['fileUrl']))
            @include('dashboard.file-link', ['url' => $uploadResult['fileUrl']])
        @endif
    </article>
@endif

<section class="block">
    <h2 class="kicker">Recent files</h2>
    @forelse ($uploads as $upload)
        <article class="bill">
            <div class="badge" style="background: {{ $upload['kind'] === 'Income' ? '#7ed06a' : '#e6c34a' }}">{{ $upload['kind'] === 'Income' ? 'I' : 'E' }}</div>
            <div>
                <b>{{ $upload['title'] }}</b>
                <small>{{ $upload['date'] }} · {{ $upload['detail'] }}</small>
            </div>
            <div class="amount">
                {{ $upload['amount'] }}
                @if ($upload['fileUrl'])
                    @include('dashboard.file-link', ['url' => $upload['fileUrl']])
                @else
                    <span>{{ $upload['kind'] }}</span>
                @endif
            </div>
        </article>
    @empty
        <p class="empty">No financial files yet.</p>
    @endforelse
    <button type="button" class="history-link" data-go="records">View history</button>
</section>
