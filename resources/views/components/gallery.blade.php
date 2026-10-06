@props(['report'])
<div class="gallery gallery-{{ min($report->photos->count(), 3) }}">
    @foreach ($report->photos as $i => $photo)
        <button type="button" class="gallery-item"
                data-zoom-src="{{ route('photos.show', $photo) }}"
                data-zoom-group="report-{{ $report->id }}"
                data-zoom-alt="Foto bukti {{ $i + 1 }} untuk {{ $report->title }}"
                aria-label="Buka dan perbesar foto bukti {{ $i + 1 }}">
            <img src="{{ route('photos.show', $photo) }}" alt="Foto bukti {{ $i + 1 }}" loading="lazy">
            <span class="gallery-zoom" aria-hidden="true"><x-icon name="zoom-in" :size="18" /></span>
        </button>
    @endforeach
</div>
