@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <div class="stack-lg">
        <section class="hero-cta">
            <div>
                <h1>Halo, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h1>
                <p style="margin-top:6px">Ada perangkat kelas yang bermasalah? Foto dulu, sisanya biar Toolman.</p>
            </div>
            <a href="{{ route('student.reports.create') }}" class="btn btn-sun btn-lg"><x-icon name="camera" :size="22" /> Foto dan laporkan</a>
        </section>

        <section class="stack">
            <h2>Sedang diproses</h2>
            @forelse ($active as $report)
                @if ($loop->first) <div class="ticket-list"> @endif
                <x-report-ticket :report="$report" :href="route('student.reports.show', $report)" />
                @if ($loop->last) </div> @endif
            @empty
                <div class="panel empty">
                    <p>Belum ada laporan yang berjalan.</p>
                </div>
            @endforelse
        </section>

        @if ($finished->isNotEmpty())
            <section class="stack">
                <h2>Baru selesai</h2>
                <div class="ticket-list">
                    @foreach ($finished as $report)
                        <x-report-ticket :report="$report" :href="route('student.reports.show', $report)" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
