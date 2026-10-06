@extends('layouts.app')

@section('title', 'Ringkasan')

@section('content')
    <div class="page-head"><h1>Ringkasan</h1></div>

    <div class="stack-lg">
        <div class="stats">
            <a href="{{ route('admin.reports.index', ['status' => 'submitted']) }}" class="stat stat-hot">
                <strong>{{ $counts['submitted'] ?? 0 }}</strong><span>Menunggu ditinjau</span>
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" class="stat">
                <strong>{{ $counts['pending'] ?? 0 }}</strong><span>Pending</span>
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'in_repair']) }}" class="stat">
                <strong>{{ $counts['in_repair'] ?? 0 }}</strong><span>Perbaikan</span>
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'done']) }}" class="stat">
                <strong>{{ $counts['done'] ?? 0 }}</strong><span>Selesai</span>
            </a>
        </div>

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
                <h2>Sedang dikerjakan</h2>
                <div class="ticket-list">
                    @foreach ($working as $report)
                        <x-report-ticket :report="$report" :href="route('admin.reports.show', $report)" :show-user="true" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
