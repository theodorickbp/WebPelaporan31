<div class="viewer" id="viewer" role="dialog" aria-modal="true" aria-label="Pratinjau foto" hidden>
    <div class="viewer-bar">
        <span class="viewer-counter" data-counter></span>
        <button type="button" class="viewer-btn" data-action="close" aria-label="Tutup pratinjau"><x-icon name="x" :size="22" /></button>
    </div>
    <div class="viewer-stage" data-stage>
        <img class="viewer-img" data-img alt="" draggable="false">
    </div>
    <div class="viewer-tools">
        <button type="button" class="viewer-btn" data-action="prev" aria-label="Foto sebelumnya"><x-icon name="chevron-left" :size="22" /></button>
        <button type="button" class="viewer-btn" data-action="zoom-out" aria-label="Perkecil"><x-icon name="zoom-out" :size="22" /></button>
        <button type="button" class="viewer-btn" data-action="reset" aria-label="Ukuran awal"><x-icon name="rotate" :size="22" /></button>
        <button type="button" class="viewer-btn" data-action="zoom-in" aria-label="Perbesar"><x-icon name="zoom-in" :size="22" /></button>
        <button type="button" class="viewer-btn" data-action="next" aria-label="Foto berikutnya"><x-icon name="chevron-right" :size="22" /></button>
    </div>
    <p class="viewer-hint">Cubit atau scroll untuk zoom, ketuk dua kali untuk perbesar, geser untuk menggerakkan.</p>
</div>
