@extends('layouts.public')

@section('title', 'Masuk')
@section('body-class', 'auth-page')

@section('content')
    <div class="auth">
        <aside class="auth-side" aria-hidden="true">
            <div class="ticket">
                <div class="ticket-main">
                    <div class="ticket-thumb"><x-icon name="wifi" :size="26" /></div>
                    <div class="ticket-body">
                        <div class="ticket-title">Kabel LAN putus</div>
                        <div class="ticket-meta"><span class="ticket-meta-item"><x-icon name="map-pin" :size="14" /> Lab RPL 1</span></div>
                    </div>
                    <span class="chip chip-done">Selesai</span>
                </div>
                <div class="ticket-stub"><span class="ticket-code">SP-D4F8ZN</span><span>kemarin</span></div>
            </div>
            <p>Laporkan, pantau, dan lihat sendiri kapan masalahnya beres.</p>
        </aside>

        <div class="auth-main">
            <x-brand />
            <div>
                <h1>Masuk</h1>
                <p class="muted" style="margin-top:6px">Siswa, guru, dan Toolman masuk dari halaman yang sama.</p>
            </div>

            @if (session('notice'))
                <p class="form-note form-note-ok" role="status">{{ session('notice') }}</p>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="stack">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required autofocus autocomplete="username" inputmode="email"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p class="field-error" id="email-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <div class="row row-between">
                        <label for="password" style="margin:0">Kata sandi</label>
                        <a href="{{ route('password.request') }}" class="small">Lupa kata sandi?</a>
                    </div>
                    <div class="input-group">
                        <input id="password" name="password" type="password" class="input" required autocomplete="current-password">
                        <button type="button" data-toggle-password="password" aria-pressed="false">Tampilkan</button>
                    </div>
                </div>

                <label class="check"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>

                <button type="submit" class="btn btn-primary btn-lg btn-block">Masuk</button>
            </form>

            <p class="muted">Belum punya akun? <a href="{{ route('register') }}">Buat Akun</a></p>
        </div>
    </div>
@endsection
