@extends('layouts.app')

@section('title', 'Riwayat laporan')

@section('content')
    <div class="page-head"><h1>Riwayat laporan</h1></div>

    @php
        $tabs = ['' => 'Semua', 'open' => 'Diproses', 'done' => 'Selesai', 'declined' => 'Ditolak'];
        $current = (string) $filter;
    @endphp
    <nav class="tabs" aria-label="Filter laporan">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('student.reports.index', $key !== '' ? ['filter' => $key] : []) }}" @class(['tab-pill', 'is-active' => $current === (string) $key]) @if ($current === (string) $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="section">
        @forelse ($reports as $report)
            @if ($loop->first) <div class="ticket-list"> @endif
            <x-report-ticket :report="$report" :href="route('student.reports.show', $report)" />
            @if ($loop->last) </div> @endif
        @empty
            <div class="panel empty">
                <p>Tidak ada laporan di sini.</p>
                <a href="{{ route('student.reports.create') }}" class="btn btn-sun"><x-icon name="camera" :size="20" /> Buat laporan</a>
            </div>
        @endforelse

        {{ $reports->links() }}
    </div>
@endsection
