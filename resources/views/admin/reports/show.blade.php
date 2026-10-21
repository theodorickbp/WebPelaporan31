@extends('layouts.app')

@section('title', $report->title)

@section('content')
    <a href="{{ route('admin.reports.index') }}" class="back-link"><x-icon name="chevron-left" :size="18" /> Semua laporan</a>

    <div class="page-head">
        <span class="code">{{ $report->code }}</span>
        <h1>{{ $report->title }}</h1>
        <div><x-status-chip :status="$report->status" /></div>
    </div>

    <div class="split">
        <div class="stack-lg">
            @if ($report->photos->isNotEmpty())
                <section class="stack">
                    <h2>Foto bukti</h2>
                    <x-gallery :report="$report" />
                    <p class="hint">Ketuk foto untuk membuka. Cubit atau scroll untuk zoom.</p>
                </section>
            @elseif ($report->wasPhotoPurged())
                <p class="hint">Foto bukti sudah dihapus otomatis karena laporan ini sudah lama {{ $report->status === \App\Enums\ReportStatus::Done ? 'selesai' : 'ditolak' }}.</p>
            @endif

            <section class="panel">
                <dl class="details">
                    <div><dt>Pelapor</dt><dd>{{ $report->user->name }}, {{ $report->user->reporterLabel() }}</dd></div>
                    <div><dt>Jenis masalah</dt><dd>{{ $report->category->label() }}</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $report->location }}</dd></div>
                    <div><dt>Dilaporkan</dt><dd>{{ $report->created_at->translatedFormat('j F Y, H:i') }}</dd></div>
                    @if ($report->description)
                        <div><dt>Keterangan</dt><dd>{!! nl2br(e($report->description)) !!}</dd></div>
                    @endif
                </dl>
            </section>
        </div>

        <aside class="split-side stack-lg">
            <section class="panel">
                <x-status-track :status="$report->status" />
            </section>

            @if ($report->status->transitions())
                <form method="POST" action="{{ route('admin.reports.transition', $report) }}" class="panel stack">
                    @csrf
                    <div class="field">
                        <label for="note">Catatan untuk siswa <span class="muted">(wajib jika ditolak)</span></label>
                        <textarea id="note" name="note" class="textarea @error('note') is-invalid @enderror" maxlength="500" rows="3">{{ old('note') }}</textarea>
                        @error('note') <p class="field-error">{{ $message }}</p> @enderror
                        @error('status') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="stack">
                        @foreach ($report->status->transitions() as $next)
                            @if ($next === \App\Enums\ReportStatus::Declined)
                                <button type="submit" name="status" value="{{ $next->value }}" class="btn btn-danger-soft btn-block" data-requires-note>{{ $next->actionLabel() }}</button>
                            @else
                                <button type="submit" name="status" value="{{ $next->value }}" class="btn btn-primary btn-block">{{ $next->actionLabel() }}</button>
                            @endif
                        @endforeach
                    </div>
                </form>
            @else
                <div class="callout {{ $report->status === \App\Enums\ReportStatus::Done ? 'callout-good' : 'callout-bad' }}">
                    <strong>{{ $report->status === \App\Enums\ReportStatus::Done ? 'Laporan selesai' : 'Laporan ditolak' }}</strong>
                    @if ($report->admin_note)<p>{{ $report->admin_note }}</p>@endif
                </div>
            @endif

            <section class="stack">
                <h2>Riwayat</h2>
                <div class="panel">@include('partials.timeline', ['report' => $report])</div>
            </section>
        </aside>
    </div>
@endsection

<x-zoom-viewer-loader />
