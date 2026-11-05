@extends('layouts.app')

@section('title', 'Ringkasan')

@section('content')
    <div class="page-head"><h1>Ringkasan</h1></div>

    <div class="stack-lg">
        @if (($pendingAccounts ?? 0) > 0)
            <section class="callout" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
                <div><strong>{{ $pendingAccounts }} pengajuan akun menunggu persetujuan</strong><span class="small">Periksa data siswa sebelum menyetujui.</span></div>
                <a class="btn btn-primary" href="{{ route('admin.accounts.index') }}">Tinjau akun</a>
            </section>
        @endif

        <div class="stats stats-auto">
            <a href="{{ route('admin.reports.index', ['status' => 'submitted']) }}" class="stat stat-hot">
                <strong>{{ $counts['submitted'] ?? 0 }}</strong><span>Menunggu ditinjau</span>
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" class="stat">
                <strong>{{ $counts['pending'] ?? 0 }}</strong><span>Disetujui</span>
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'in_repair']) }}" class="stat">
                <strong>{{ $counts['in_repair'] ?? 0 }}</strong><span>Dikerjakan</span>
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'review']) }}" class="stat">
                <strong>{{ $counts['review'] ?? 0 }}</strong><span>Perlu diperiksa</span>
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'done']) }}" class="stat">
                <strong>{{ $counts['done'] ?? 0 }}</strong><span>Selesai</span>
            </a>
        </div>

        <p class="capacity">
            <span>Antrean Admin: <b @class(['is-full' => ($counts['submitted'] ?? 0) >= $limits['max_queue']])>{{ $counts['submitted'] ?? 0 }}/{{ $limits['max_queue'] }}</b></span>
            <span>Menunggu antrean: <b>{{ $counts['waiting'] ?? 0 }}</b></span>
            <span>Disetujui sekaligus: <b @class(['is-full' => $limits['active'] >= $limits['max_active']])>{{ $limits['active'] }}/{{ $limits['max_active'] }}</b></span>
        </p>

        @if ($review->isNotEmpty())
            <section class="stack">
                <h2>Hasil pengerjaan perlu diperiksa</h2>
                <div class="ticket-list">
                    @foreach ($review as $report)
                        <x-report-ticket :report="$report" :href="route('admin.reports.show', $report)" :show-user="true" />
                    @endforeach
                </div>
            </section>
        @endif

        <section class="stack">
            <div class="row row-between">
                <h2>Antrean laporan baru</h2>
                <a href="{{ route('admin.reports.index') }}">Lihat semua</a>
            </div>
            @forelse ($queue as $report)
                @if ($loop->first) <div class="ticket-list"> @endif
                <x-report-ticket :report="$report" :href="route('admin.reports.show', $report)" :show-user="true" />
                @if ($loop->last) </div> @endif
            @empty
                <div class="panel empty"><p>Tidak ada laporan yang menunggu. Semua sudah ditinjau.</p></div>
            @endforelse
        </section>

        @if ($working->isNotEmpty())
            <section class="stack">
                <h2>Disetujui &amp; sedang dikerjakan</h2>
                <div class="ticket-list">
                    @foreach ($working as $report)
                        <x-report-ticket :report="$report" :href="route('admin.reports.show', $report)" :show-user="true" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
