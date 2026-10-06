@extends('layouts.public')

@section('title', 'Daftar')
@section('body-class', 'auth-page')

@section('content')
    <div class="auth">
        <aside class="auth-side" aria-hidden="true">
            <div class="ticket">
                <div class="ticket-main">
                    <div class="ticket-thumb"><x-icon name="mouse" :size="26" /></div>
                    <div class="ticket-body">
                        <div class="ticket-title">Mouse hilang dari meja 5</div>
                        <div class="ticket-meta"><span class="ticket-meta-item"><x-icon name="map-pin" :size="14" /> Kelas XI TKJ 1</span></div>
                    </div>
                    <span class="chip chip-pending">Pending</span>
                </div>
                <div class="ticket-stub"><span class="ticket-code">SP-H2R9WA</span><span>3 jam lalu</span></div>
            </div>
            <p>Satu akun untuk semua laporanmu, bisa dibuka dari HP maupun laptop.</p>
        </aside>

        <div class="auth-main">
            <x-brand />
            <div>
                <h1>Buat akun siswa</h1>
                <p class="muted" style="margin-top:6px">Akun Toolman dibuat oleh sekolah, bukan lewat halaman ini.</p>
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="stack">
                @csrf
                <div class="field">
                    <label for="name">Nama lengkap</label>
                    <input id="name" name="name" type="text" class="input @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="name" maxlength="80">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="kelas">Kelas</label>
                    <input id="kelas" name="kelas" type="text" class="input @error('kelas') is-invalid @enderror" value="{{ old('kelas') }}" required maxlength="30" placeholder="Contoh: XI RPL 1">
                    @error('kelas') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email" inputmode="email">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="password">Kata sandi</label>
                    <div class="input-group">
                        <input id="password" name="password" type="password" class="input @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
                        <button type="button" data-toggle-password="password" aria-pressed="false">Tampilkan</button>
                    </div>
                    <p class="hint">Minimal 8 karakter.</p>
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Ulangi kata sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">Buat akun</button>
            </form>

            <p class="muted">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div>
    </div>
@endsection
