@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
    <div class="page-head">
        <div class="row row-between">
            <h1>Notifikasi</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-soft" style="min-height:42px">Tandai semua dibaca</button>
                </form>
            @endif
        </div>
    </div>

    @forelse ($notifications as $n)
        @if ($loop->first) <div class="notes"> @endif
        @php $st = \App\Enums\ReportStatus::tryFrom($n->data['status'] ?? ''); @endphp
        <a href="{{ route('notifications.open', $n->id) }}" @class(['note', 'is-unread' => is_null($n->read_at)])>
            <span class="note-dot tone-{{ $st?->tone() ?? 'wait' }}"><x-icon :name="$st?->icon() ?? 'bell'" :size="18" /></span>
            <span class="note-body">
                <strong>{{ $n->data['title'] ?? 'Laporan' }}</strong>
                <span>{{ $n->data['message'] ?? '' }}</span>
                <time datetime="{{ $n->created_at->toIso8601String() }}">{{ $n->created_at->diffForHumans() }}</time>
            </span>
        </a>
        @if ($loop->last) </div> @endif
    @empty
        <div class="panel empty"><p>Belum ada notifikasi. Kabar terbaru tentang laporan akan muncul di sini.</p></div>
    @endforelse

    <div class="section">{{ $notifications->links() }}</div>
@endsection
