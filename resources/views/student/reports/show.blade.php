@extends('layouts.app')

@section('title', $report->title)

@section('content')
    <a href="{{ route('student.reports.index') }}" class="back-link"><x-icon name="chevron-left" :size="18" /> Riwayat</a>

    <div class="page-head">
        <span class="code">{{ $report->code }}</span>
        <h1>{{ $report->title }}</h1>
        <div><x-status-chip :status="$report->status" /></div>
    </div>

    <div class="stack-lg">
        <section class="panel">
            <x-status-track :status="$report->status" />
        </section>

        @if ($report->admin_note)
            <div class="callout {{ $report->status === \App\Enums\ReportStatus::Declined ? 'callout-bad' : ($report->status === \App\Enums\ReportStatus::Done ? 'callout-good' : '') }}">
                <strong>Catatan Toolman</strong>
                <p>{{ $report->admin_note }}</p>
            </div>
        @endif

        @if ($report->photos->isNotEmpty())
            <section class="stack">
                <h2>Foto bukti</h2>
                <x-gallery :report="$report" />
                <p class="hint">Ketuk foto untuk membuka dan memperbesar.</p>
            </section>
        @elseif ($report->wasPhotoPurged())
            <p class="hint">Foto bukti sudah dihapus otomatis karena laporan ini sudah lama {{ $report->status === \App\Enums\ReportStatus::Done ? 'selesai' : 'ditolak' }}.</p>
        @endif

        <section class="panel">
            <dl class="details">
                <div><dt>Jenis masalah</dt><dd>{{ $report->category->label() }}</dd></div>
                <div><dt>Lokasi</dt><dd>{{ $report->location }}</dd></div>
                <div><dt>Dilaporkan</dt><dd>{{ $report->created_at->translatedFormat('j F Y, H:i') }}</dd></div>
                @if ($report->description)
                    <div><dt>Keterangan</dt><dd>{!! nl2br(e($report->description)) !!}</dd></div>
                @endif
            </dl>
        </section>

        <section class="stack">
            <h2>Riwayat</h2>
            <div class="panel">@include('partials.timeline', ['report' => $report])</div>
        </section>

        @can('delete', $report)
            <form method="POST" action="{{ route('student.reports.destroy', $report) }}" data-confirm="Batalkan laporan ini? Foto dan isinya akan dihapus.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-soft btn-block"><x-icon name="trash" :size="18" /> Batalkan laporan</button>
                <p class="hint" style="margin-top:8px">Bisa dibatalkan selama Toolman belum meninjau laporan ini.</p>
            </form>
        @endcan
    </div>
@endsection

<x-zoom-viewer-loader />
