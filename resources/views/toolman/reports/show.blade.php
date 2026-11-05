@extends('layouts.app')

@section('title', $report->title)

@section('content')
    @php $canWork = auth()->user()->can('work', $report); @endphp

    <a href="{{ route('toolman.reports.index') }}" class="back-link"><x-icon name="chevron-left" :size="18" /> Semua tugas</a>

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
                <p class="hint">Foto bukti sudah dihapus otomatis karena laporan ini sudah lama selesai.</p>
            @endif

            <section class="panel">
                <dl class="details">
                    <div><dt>Pelapor</dt><dd>{{ $report->user->name }}, kelas {{ $report->user->kelas }}</dd></div>
                    <div><dt>Jenis masalah</dt><dd>{{ $report->category->label() }}</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $report->location }}</dd></div>
                    <div><dt>Dilaporkan</dt><dd>{{ $report->created_at->translatedFormat('j F Y, H:i') }}</dd></div>
                    @if ($report->description)
                        <div><dt>Keterangan</dt><dd>{!! nl2br(e($report->description)) !!}</dd></div>
                    @endif
                    @if ($report->work_note)
                        <div><dt>Hasil pengerjaan yang kamu kirim</dt><dd>{!! nl2br(e($report->work_note)) !!}</dd></div>
                    @endif
                </dl>
            </section>
        </div>

        <aside class="split-side stack-lg">
            @if ($canWork)
                <form method="POST" action="{{ route('toolman.reports.submit', $report) }}" class="panel stack" data-once>
                    @csrf
                    <h2>Kirim hasil pengerjaan</h2>
                    <div class="field">
                        <label for="note">Hasil pengerjaan <span class="req" aria-hidden="true">*</span></label>
                        <textarea id="note" name="note" class="textarea @error('note') is-invalid @enderror" maxlength="1000" rows="5" required minlength="5" placeholder="Apa yang sudah dikerjakan dan bagaimana kondisinya sekarang.">{{ old('note') }}</textarea>
                        @error('note') <p class="field-error">{{ $message }}</p> @enderror
                        @error('status') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" data-busy="Mengirim…">Kirim ke Admin</button>
                    <p class="hint">Admin akan memeriksa hasilnya, lalu menyelesaikan laporan dan memberi tahu siswa.</p>
                </form>
            @elseif ($report->status === \App\Enums\ReportStatus::Review)
                <div class="callout"><strong>Menunggu pemeriksaan Admin</strong><p>Hasil pengerjaanmu sudah terkirim.</p></div>
            @elseif ($report->status === \App\Enums\ReportStatus::Done)
                <div class="callout callout-good"><strong>Laporan selesai</strong>@if ($report->admin_note)<p>{{ $report->admin_note }}</p>@endif</div>
            @endif

            <section class="stack">
                <h2>Riwayat</h2>
                <div class="panel">@include('partials.timeline', ['report' => $report])</div>
            </section>
        </aside>
    </div>
@endsection

<x-zoom-viewer-loader />
