@props(['report'])
<div class="gallery gallery-{{ min($report->photos->count(), 3) }}">
    {{-- CARI: GALERI FOTO LAPORAN — mengulang seluruh foto laporan, memakai route thumbnail dan URL foto asli untuk zoom viewer. --}}
@foreach ($report->photos as $i => $photo)
        <button type="button" class="gallery-item"
                data-zoom-src="{{ route('photos.show', $photo) }}"
                data-zoom-group="report-{{ $report->id }}"
                data-zoom-alt="Foto bukti {{ $i + 1 }} untuk {{ $report->title }}"
                aria-label="Buka dan perbesar foto bukti {{ $i + 1 }}">
            <img src="{{ route('photos.thumb', $photo) }}" alt="Foto bukti {{ $i + 1 }}" decoding="async" width="480" height="360">
            <span class="gallery-zoom" aria-hidden="true"><x-icon name="zoom-in" :size="18" /></span>
        </button>
    @endforeach
</div>
