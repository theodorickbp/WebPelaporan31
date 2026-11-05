@extends('layouts.app')

@section('title', 'Beranda Toolman')

@section('content')
    <div class="page-head"><h1>Halo, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }} {{ \Illuminate\Support\Str::after(auth()->user()->name, ' ') }}</h1></div>

    <div class="stack-lg">
        <div class="stats stats-auto">
            <a href="{{ route('toolman.reports.index', ['status' => 'in_repair']) }}" class="stat stat-hot">
                <strong>{{ $counts['in_repair'] ?? 0 }}</strong><span>Perlu dikerjakan</span>
            </a>
            <a href="{{ route('toolman.reports.index', ['status' => 'review']) }}" class="stat">
                <strong>{{ $counts['review'] ?? 0 }}</strong><span>Diperiksa Admin</span>
            </a>
            <a href="{{ route('toolman.reports.index', ['status' => 'done']) }}" class="stat">
                <strong>{{ $counts['done'] ?? 0 }}</strong><span>Selesai</span>
            </a>
        </div>

        <section class="stack">
            <h2>Tugas untukmu</h2>
            @forelse ($todo as $report)
                @if ($loop->first) <div class="ticket-list"> @endif
                <x-report-ticket :report="$report" :href="route('toolman.reports.show', $report)" :show-user="true" />
                @if ($loop->last) </div> @endif
            @empty
                <div class="panel empty"><p>Belum ada tugas. Admin akan menugaskan laporan kepadamu.</p></div>
            @endforelse
        </section>

        @if ($waiting->isNotEmpty())
            <section class="stack">
                <h2>Menunggu diperiksa Admin</h2>
                <div class="ticket-list">
                    @foreach ($waiting as $report)
                        <x-report-ticket :report="$report" :href="route('toolman.reports.show', $report)" :show-user="true" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
