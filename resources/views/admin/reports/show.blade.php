@extends('layouts.app')

@section('title', $report->title)

@section('content')
    <a href="{{ route('admin.reports.index') }}" class="back-link"><x-icon name="chevron-left" :size="18" /> Semua laporan</a>

    <div class="page-head">
        <span class="code">{{ $report->code }}</span>
        <h1>{{ $report->title }}</h1>
        <div><x-status-chip :status="$report->status" /></div>
    </div>

    @php
        $S = \App\Enums\ReportStatus::class;
    @endphp

    <div class="split">
        <div class="stack-lg">
            @if ($report->photos->isNotEmpty())
                <section class="stack">
                    <h2>Foto bukti</h2>
{{-- CARI: FOTO BUKTI DI HALAMAN ADMIN — menampilkan galeri foto yang diunggah siswa. --}}
                    <x-gallery :report="$report" />
                    <p class="hint">Ketuk foto untuk membuka. Cubit atau scroll untuk zoom.</p>
                </section>
            @elseif ($report->wasPhotoPurged())
                <p class="hint">Foto bukti sudah dihapus otomatis karena laporan ini sudah lama {{ $report->status === \App\Enums\ReportStatus::Done ? 'selesai' : 'ditolak' }}.</p>
            @endif

            <section class="panel">
                <dl class="details">
                    <div><dt>Pelapor</dt><dd>{{ $report->user->name }}, kelas {{ $report->user->kelas }}</dd></div>
                    <div><dt>Jenis masalah</dt><dd>{{ $report->category->label() }}</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $report->location }}</dd></div>
                    <div><dt>Dilaporkan</dt><dd>{{ $report->created_at->translatedFormat('j F Y, H:i') }}</dd></div>
                    @if ($report->toolman)
                        <div><dt>Toolman yang menangani</dt><dd>{{ $report->toolman->name }}</dd></div>
                    @endif
                    @if ($report->description)
                        <div><dt>Keterangan</dt><dd>{!! nl2br(e($report->description)) !!}</dd></div>
                    @endif
                    @if ($report->work_note)
                        <div><dt>Hasil pengerjaan dari Toolman</dt><dd>{!! nl2br(e($report->work_note)) !!}</dd></div>
                    @endif
                </dl>
            </section>
        </div>

        <aside class="split-side stack-lg">
            <section class="panel">
                <x-status-track :status="$report->status" />
            </section>

            @if ($report->status === $S::Waiting)
                <div class="callout">
                    <strong>Menunggu antrean</strong>
                    <p>Antrean Admin penuh (maks. {{ config('sipasar.max_queue') }}). Laporan ini otomatis masuk antrean begitu ada slot kosong.</p>
                </div>
            @elseif ($report->status === $S::Pending)
                {{-- Disetujui: pilih Toolman secara manual --}}
{{-- CARI: FORM PILIH TOOLMAN — Admin memilih petugas secara manual lalu mengirimnya ke route admin.reports.assign. --}}
                <form method="POST" action="{{ route('admin.reports.assign', $report) }}" class="panel stack" data-once>
                    @csrf
                    <h2>Tugaskan ke Toolman</h2>
                    <div class="field">
                        <label for="toolman_id">Pilih Toolman <span class="req" aria-hidden="true">*</span></label>
                        <select id="toolman_id" name="toolman_id" class="select @error('toolman_id') is-invalid @enderror" required>
                            <option value="">— Pilih Toolman —</option>
                            @foreach ($toolmen as $t)
                                <option value="{{ $t->id }}" @selected((string) old('toolman_id') === (string) $t->id)>{{ $t->name }} ({{ $workload[$t->id] ?? 0 }} tugas aktif)</option>
                            @endforeach
                        </select>
                        @error('toolman_id') <p class="field-error">{{ $message }}</p> @enderror
                        @error('status') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" data-busy="Menugaskan…">Tugaskan</button>
                </form>
            @elseif ($report->status === $S::InRepair)
                <div class="callout">
                    <strong>Sedang dikerjakan{{ $report->toolman ? ' oleh '.$report->toolman->name : '' }}</strong>
                    <p>Menunggu Toolman mengirim hasil pengerjaan.</p>
                </div>
            @elseif ($report->status->transitions())
{{-- CARI: TOMBOL AKSI STATUS ADMIN — tombol setujui/tolak/selesaikan/minta revisi mengirim nilai status ke ReportController::transition(). --}}
                <form method="POST" action="{{ route('admin.reports.transition', $report) }}" class="panel stack">
                    @csrf
                    <div class="field">
                        @if ($report->status === $S::Review)
                            <label for="note">Informasi untuk siswa <span class="req" aria-hidden="true">*</span> <span class="muted">(juga dipakai sebagai catatan revisi jika dikembalikan ke Toolman)</span></label>
                        @else
                            <label for="note">Catatan untuk siswa <span class="muted">(wajib jika ditolak)</span></label>
                        @endif
                        <textarea id="note" name="note" class="textarea @error('note') is-invalid @enderror" maxlength="500" rows="3">{{ old('note') }}</textarea>
                        @error('note') <p class="field-error">{{ $message }}</p> @enderror
                        @error('status') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    @if ($report->status === $S::Submitted)
                        <p class="hint">Disetujui sekarang: <strong>{{ $activeCount }}/{{ $maxActive }}</strong> laporan.</p>
                    @endif
                    <div class="stack">
                        @foreach ($report->status->transitions() as $next)
                            @if ($next === $S::Declined)
                                <button type="submit" name="status" value="{{ $next->value }}" class="btn btn-danger-soft btn-block" data-requires-note>{{ $next->actionLabel() }}</button>
                            @elseif ($next === $S::InRepair)
                                <button type="submit" name="status" value="{{ $next->value }}" class="btn btn-soft btn-block" data-requires-note data-note-msg="Tulis catatan revisi untuk Toolman.">{{ $next->actionLabel() }}</button>
                            @else
                                <button type="submit" name="status" value="{{ $next->value }}" class="btn btn-primary btn-block" @if ($next === $S::Done) data-requires-note data-note-msg="Tulis informasi untuk siswa sebelum menyelesaikan laporan." @endif>{{ $next->actionLabel() }}</button>
                            @endif
                        @endforeach
                    </div>
                </form>
            @else
                <div class="callout {{ $report->status === $S::Done ? 'callout-good' : 'callout-bad' }}">
                    <strong>{{ $report->status === $S::Done ? 'Laporan selesai' : 'Laporan ditolak' }}</strong>
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
