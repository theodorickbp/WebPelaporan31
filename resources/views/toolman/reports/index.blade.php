@extends('layouts.app')

@section('title', 'Tugas')

@section('content')
    <div class="page-head"><h1>Tugas</h1></div>

    @php
        $tabs = [
            ['key' => 'in_repair', 'label' => 'Dikerjakan'],
            ['key' => 'review', 'label' => 'Diperiksa Admin'],
            ['key' => 'done', 'label' => 'Selesai'],
            ['key' => 'all', 'label' => 'Semua'],
        ];
        $total = array_sum($counts);
    @endphp

    <nav class="tabs" aria-label="Filter status">
        @foreach ($tabs as $tab)
            @php $n = $tab['key'] === 'all' ? $total : ($counts[$tab['key']] ?? 0); @endphp
            <a href="{{ route('toolman.reports.index', array_filter(['status' => $tab['key'], 'q' => $q])) }}"
               @class(['tab-pill', 'is-active' => $status === $tab['key']]) @if ($status === $tab['key']) aria-current="page" @endif>
                {{ $tab['label'] }} <span class="count">{{ $n }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('toolman.reports.index') }}" class="filters section">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="field">
            <label for="q">Cari</label>
            <input id="q" name="q" type="search" class="input" value="{{ $q }}" placeholder="Kode, judul, lokasi, atau nama siswa">
        </div>
        <button type="submit" class="btn btn-soft"><x-icon name="search" :size="18" /> Terapkan</button>
    </form>

    <div class="section">
        @forelse ($reports as $report)
            @if ($loop->first) <div class="ticket-list"> @endif
            <x-report-ticket :report="$report" :href="route('toolman.reports.show', $report)" :show-user="true" />
            @if ($loop->last) </div> @endif
        @empty
            <div class="panel empty"><p>Tidak ada tugas pada filter ini.</p></div>
        @endforelse

        {{ $reports->links() }}
    </div>
@endsection
