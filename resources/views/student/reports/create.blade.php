@extends('layouts.app')

@section('title', 'Buat laporan')

@section('content')
    <div class="page-head">
        <h1>Buat laporan</h1>
        <p class="muted">Mulai dari foto buktinya, lalu isi detail singkat.</p>
    </div>

    <form method="POST" action="{{ route('student.reports.store') }}" enctype="multipart/form-data" class="stack-lg" data-once>
        @csrf

        @if ($remaining <= 0)
            <div class="callout callout-bad"><strong>Batas harian tercapai</strong><p>Kamu sudah membuat {{ $dailyMax }} laporan hari ini. Coba lagi besok.</p></div>
        @else
            <p class="hint">Sisa jatah laporan hari ini: <strong>{{ $remaining }}</strong> dari {{ $dailyMax }}. Tanda <span class="req">*</span> wajib diisi.</p>
        @endif
        @error('limit') <div class="callout callout-bad"><strong>{{ $message }}</strong></div> @enderror

        {{-- 1. Foto bukti --}}
{{-- CARI: KOMPONEN UNGGAH FOTO BUKTI — data-max/data-max-mb mengirim batas jumlah dan ukuran ke JavaScript photo-uploader.js. --}}
        <section class="uploader" data-uploader data-max="{{ config('sipasar.max_photos') }}" data-max-mb="{{ round(config('sipasar.max_photo_kb') / 1024) }}">
            <h2 id="photo-title">Foto bukti <span class="req" aria-hidden="true">*</span></h2>

{{-- CARI: INPUT FILE UTAMA photos[] — file foto yang sudah dipilih/diproses akan disinkronkan ke input ini sebelum submit. --}}
            <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/gif,image/bmp" data-real hidden>
{{-- CARI: INPUT KAMERA DAN GALERI — capture environment meminta kamera belakang di perangkat yang mendukung; input lain membuka galeri/file. --}}
            <input type="file" accept="image/jpeg,image/png,image/gif,image/bmp" capture="environment" data-camera-input hidden>
            <input type="file" accept="image/jpeg,image/png,image/gif,image/bmp" multiple data-gallery-input hidden>

            <div class="dropzone" data-drop role="group" aria-labelledby="photo-title">
                <span class="dropzone-icon"><x-icon name="camera" :size="28" /></span>
                <p>
                    <span class="dz-mobile">Jika kamera tersedia, kamera akan terbuka. Jika tidak, pilih foto dari folder perangkat.</span>
                    <span class="dz-desktop">Seret foto ke kotak ini, tempel dengan Ctrl+V, atau pakai tombol di bawah.</span>
                </p>
                <div class="dropzone-actions">
{{-- CARI: TOMBOL AMBIL FOTO DAN PILIH GALERI — data-action dibaca oleh public/js/photo-uploader.js. --}}
                    <button type="button" class="btn btn-primary" data-action="camera"><x-icon name="camera" :size="20" /> Ambil foto</button>
                    <button type="button" class="btn btn-soft" data-action="gallery"><x-icon name="image" :size="20" /> Pilih dari galeri</button>
                </div>
            </div>

            <ul class="thumbs" data-thumbs></ul>
            <p class="hint" data-count>0 dari {{ config('sipasar.max_photos') }} foto</p>
            <p class="uploader-status" data-status role="status" aria-live="polite"></p>

            @error('photos') <p class="field-error">{{ $message }}</p> @enderror
            @foreach ($errors->get('photos.*') as $messages)
                @foreach ($messages as $message) <p class="field-error">{{ $message }}</p> @endforeach
            @endforeach
            @if ($errors->any() && ! $errors->has('photos') && ! count($errors->get('photos.*')))
                <p class="callout">Halaman dimuat ulang karena ada isian yang perlu diperbaiki. Pilih lagi foto buktinya.</p>
            @endif

            {{-- Kamera browser untuk laptop --}}
{{-- CARI: TAMPILAN MODAL KAMERA — elemen video menampilkan kamera, tombol snap mengambil foto, tombol close menghentikan kamera. --}}
            <div class="camera" data-camera hidden role="dialog" aria-modal="true" aria-label="Kamera">
                <video autoplay playsinline muted></video>
                <div class="camera-bar">
                    <button type="button" class="btn btn-ghost" style="color:#fff;border-color:rgba(255,255,255,.4)" data-action="close-camera">Selesai</button>
                    <button type="button" class="camera-shutter" data-action="snap" aria-label="Ambil foto"></button>
                    <span class="camera-count" data-camera-count>0 / {{ config('sipasar.max_photos') }}</span>
                </div>
            </div>
        </section>

{{-- CARI: PILIH KATEGORI KERUSAKAN — radio button kategori diisi dari enum ReportCategory. --}}
        {{-- 2. Jenis masalah --}}
        <section class="stack">
            <h2 id="cat-title">Masalahnya apa? <span class="req" aria-hidden="true">*</span></h2>
            <div class="choices" role="radiogroup" aria-labelledby="cat-title">
                @foreach ($categories as $cat)
                    <label class="choice">
                        <input type="radio" name="category" value="{{ $cat->value }}" @checked(old('category') === $cat->value) required>
                        <span><x-icon :name="$cat->icon()" :size="18" /> {{ $cat->label() }}</span>
                    </label>
                @endforeach
            </div>
            @error('category') <p class="field-error">{{ $message }}</p> @enderror
        </section>

{{-- CARI: INPUT JUDUL, LOKASI, DAN DESKRIPSI LAPORAN — data teks yang dikirim bersama foto bukti. --}}
        {{-- 3. Detail --}}
        <section class="stack">
            <div class="field">
                <label for="title">Judul singkat <span class="req" aria-hidden="true">*</span></label>
                <input id="title" name="title" type="text" class="input @error('title') is-invalid @enderror" value="{{ old('title') }}" required minlength="5" maxlength="120" placeholder="Contoh: Proyektor tidak menyala">
                @error('title') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="location">Lokasi <span class="req" aria-hidden="true">*</span></label>
                <input id="location" name="location" type="text" class="input @error('location') is-invalid @enderror" value="{{ old('location') }}" required maxlength="120" placeholder="Contoh: Lab RPL 2, meja 5">
                @error('location') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="description">Keterangan <span class="muted">(boleh dikosongkan)</span></label>
                <textarea id="description" name="description" class="textarea @error('description') is-invalid @enderror" maxlength="2000" placeholder="Sejak kapan rusak, apa yang terjadi, dan hal lain yang perlu diketahui Admin dan Toolman.">{{ old('description') }}</textarea>
                @error('description') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </section>

        <button type="submit" class="btn btn-primary btn-lg btn-block" data-busy="Mengirim…">Kirim laporan</button>
    </form>
@endsection

<x-zoom-viewer-loader />

@push('scripts')
    <script src="{{ asset('js/photo-uploader.js') }}?v={{ @filemtime(public_path('js/photo-uploader.js')) }}" defer></script>
@endpush
