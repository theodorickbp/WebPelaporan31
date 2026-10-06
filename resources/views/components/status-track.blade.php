@props(['status'])
@php
    $steps = ['Terkirim', 'Pending', 'Perbaikan', 'Selesai'];
    $current = match ($status) {
        \App\Enums\ReportStatus::Submitted => 0,
        \App\Enums\ReportStatus::Pending => 1,
        \App\Enums\ReportStatus::InRepair => 2,
        \App\Enums\ReportStatus::Done => 3,
        default => null,
    };
    $doneUpTo = $status === \App\Enums\ReportStatus::Done ? 3 : ($current ?? 0) - 1;
@endphp

@if ($status === \App\Enums\ReportStatus::Declined)
    <div class="track-declined">
        <x-icon name="x-circle" :size="20" />
        <span>Laporan ini ditolak. Alasannya ada di catatan Toolman.</span>
    </div>
@else
    <ol @class(['track', 'track-complete' => $status === \App\Enums\ReportStatus::Done]) aria-label="Tahap laporan">
        @foreach ($steps as $i => $label)
            <li @class(['track-step', 'is-done' => $i <= $doneUpTo, 'is-current' => $i === $current && $status !== \App\Enums\ReportStatus::Done]) @if ($i === $current) aria-current="step" @endif>
                <span class="track-dot">
                    @if ($i <= $doneUpTo)
                        <x-icon name="check" :size="14" />
                    @endif
                </span>
                <span class="track-label">{{ $label }}</span>
            </li>
        @endforeach
    </ol>
@endif
