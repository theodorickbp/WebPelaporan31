@extends('layouts.public')

@section('title', 'Laporkan kerusakan sarana kelas')

@section('content')
    <header class="pub-top">
        <x-brand />
        <div class="row">
            <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
            <a href="{{ route('register') }}" class="btn btn-primary">Buat Akun</a>
        </div>
    </header>

    <section class="hero">
        <div>
            <h1>Ada yang rusak di kelas? Foto dan laporkan sekarang.</h1>
            <p class="hero-lead">SI-PASAR menghubungkan siswa dengan Toolman. Kirim foto bukti dari HP, lalu pantau perbaikannya sampai selesai.</p>
            <div class="hero-actions">
                <a href="{{ route('register') }}" class="btn btn-sun btn-lg"><x-icon name="camera" :size="22" /> Buat laporan</a>
                <a href="{{ route('login') }}" class="btn btn-soft btn-lg">Sudah punya akun</a>
            </div>
        </div>

        <div class="hero-sample" aria-hidden="true">
            <div class="ticket">
                <div class="ticket-main">
                    <div class="ticket-thumb"><x-icon name="projector" :size="26" /></div>
                    <div class="ticket-body">
                        <div class="ticket-title">Proyektor tidak menyala</div>
                        <div class="ticket-meta">
                            <span class="ticket-meta-item"><x-icon name="map-pin" :size="14" /> Lab RPL 2</span>
                            <span class="ticket-meta-item"><x-icon name="projector" :size="14" /> Proyektor</span>
                        </div>
                    </div>
                    <span class="chip chip-repair">Perbaikan</span>
                </div>
                <div class="ticket-stub">
                    <span class="ticket-code">SP-K7M3QX</span>
                    <span>2 jam lalu</span>
                </div>
            </div>
            <div class="panel">
                <div class="sample-photo"><x-icon name="image" :size="34" /></div>
                <div style="margin-top:16px">
                    <ol class="track" style="--x:0">
                        <li class="track-step is-done"><span class="track-dot"><x-icon name="check" :size="14" /></span><span class="track-label">Terkirim</span></li>
                        <li class="track-step is-done"><span class="track-dot"><x-icon name="check" :size="14" /></span><span class="track-label">Pending</span></li>
                        <li class="track-step is-current"><span class="track-dot"></span><span class="track-label">Perbaikan</span></li>
                        <li class="track-step"><span class="track-dot"></span><span class="track-label">Selesai</span></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="how">
        <div class="how-inner">
            <h2>Cara kerjanya</h2>
            <ol>
                <li>
                    <div>
                        <h3>Foto masalahnya</h3>
                        <p>Ambil foto dari kamera atau pilih dari galeri. Di laptop, seret fotonya ke kotak unggah atau pakai kamera laptop.</p>
                    </div>
                </li>
                <li>
                    <div>
                        <h3>Kirim ke Toolman</h3>
                        <p>Pilih jenis masalah, tulis lokasinya, lalu kirim. Toolman akan menerima atau menolak laporanmu.</p>
                    </div>
                </li>
                <li>
                    <div>
                        <h3>Pantau sampai selesai</h3>
                        <p>Status bergerak dari pending, perbaikan, sampai selesai. Kamu dapat notifikasi di setiap tahap.</p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <section class="cats">
        <h2>Yang bisa kamu laporkan</h2>
        <div class="choices">
            @foreach (\App\Enums\ReportCategory::cases() as $cat)
                <div class="choice"><span><x-icon :name="$cat->icon()" :size="18" /> {{ $cat->label() }}</span></div>
            @endforeach
        </div>
    </section>

    <footer class="pub-foot">SI-PASAR, Sistem Laporan Pengaduan Sarana</footer>
@endsection
