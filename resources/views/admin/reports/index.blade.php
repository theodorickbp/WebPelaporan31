@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    <div class="page-head"><h1>Laporan</h1></div>

    @php
        $tabs = [
            ['key' => 'submitted', 'label' => 'Menunggu ditinjau'],
            ['key' => 'pending', 'label' => 'Pending'],
            ['key' => 'in_repair', 'label' => 'Perbaikan'],
            ['key' => 'done', 'label' => 'Selesai'],
            ['key' => 'declined', 'label' => 'Ditolak'],
            ['key' => 'all', 'label' => 'Semua'],
        ];
        $total = array_sum($counts);
    @endphp

    <nav class="tabs" aria-label="Filter status">
        @foreach ($tabs as $tab)
            @php $n = $tab['key'] === 'all' ? $total : ($counts[$tab['key']] ?? 0); @endphp
            <a href="{{ route('admin.reports.index', array_filter(['status' => $tab['key'], 'category' => $category, 'q' => $q])) }}"
               @class(['tab-pill', 'is-active' => $status === $tab['key']]) @if ($status === $tab['key']) aria-current="page" @endif>
                {{ $tab['label'] }} <span class="count">{{ $n }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="filters section">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="field">
            <label for="q">Cari</label>
            <input id="q" name="q" type="search" class="input" value="{{ $q }}" placeholder="Kode, judul, lokasi, atau nama siswa">
        </div>
        <div class="field">
            <label for="category">Jenis masalah</label>
            <select id="category" name="category" class="select">
                <option value="">Semua jenis</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->value }}" @selected($category === $cat->value)>{{ $cat->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-soft"><x-icon name="search" :size="18" /> Terapkan</button>
    </form>

    <div class="section">
        @forelse ($reports as $report)
            @if ($loop->first) <div class="ticket-list"> @endif
            <x-report-ticket :report="$report" :href="route('admin.reports.show', $report)" :show-user="true" />
            @if ($loop->last) </div> @endif
        @empty
            <div class="panel empty"><p>Tidak ada laporan yang cocok dengan filter ini.</p></div>
        @endforelse

        {{ $reports->links() }}
    </div>
@endsection
