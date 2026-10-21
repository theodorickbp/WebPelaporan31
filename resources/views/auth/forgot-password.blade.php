@extends('layouts.public')

@section('title', 'Lupa kata sandi')
@section('body-class', 'auth-page')

@section('content')
    <div class="auth">
        <aside class="auth-side" aria-hidden="true">
            <div class="ticket">
                <div class="ticket-main">
                    <div class="ticket-thumb"><x-icon name="user" :size="26" /></div>
                    <div class="ticket-body">
                        <div class="ticket-title">Lupa kata sandi? Tenang.</div>
                        <div class="ticket-meta"><span class="ticket-meta-item">Kode OTP dikirim ke email</span></div>
                    </div>
                </div>
                <div class="ticket-stub"><span class="ticket-code">Siswa &amp; guru</span><span>~1 menit</span></div>
            </div>
            <p>Masukkan email yang kamu pakai saat membuat akun, nanti kode OTP dikirim ke sana.</p>
        </aside>

        <div class="auth-main">
            <x-brand />
            <div>
                <h1>Lupa kata sandi</h1>
                <p class="muted" style="margin-top:6px">Untuk akun siswa dan guru yang sudah aktif. Masukkan email yang kamu pakai saat membuat akun.</p>
            </div>

            <form method="POST" action="{{ route('password.email') }}" class="stack" data-once>
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus autocomplete="email" inputmode="email">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block" data-busy="Mengirim…">Kirim kode OTP</button>
            </form>

            <p class="muted"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
        </div>
    </div>
@endsection
