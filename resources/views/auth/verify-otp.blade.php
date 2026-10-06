@extends('layouts.public')

@section('title', 'Verifikasi kode OTP')
@section('body-class', 'auth-page')

@section('content')
    <div class="auth">
        <aside class="auth-side" aria-hidden="true">
            <div class="ticket">
                <div class="ticket-main">
                    <div class="ticket-thumb"><x-icon name="check" :size="26" /></div>
                    <div class="ticket-body">
                        <div class="ticket-title">Cek email kamu</div>
                        <div class="ticket-meta"><span class="ticket-meta-item">Kode berlaku {{ config('sipasar.otp.expires_minutes') }} menit</span></div>
                    </div>
                </div>
                <div class="ticket-stub"><span class="ticket-code">6 digit</span><span>sekali pakai</span></div>
            </div>
            <p>Kalau tidak ketemu di kotak masuk, coba cek folder spam/promosi.</p>
        </aside>

        <div class="auth-main">
            <x-brand />
            <div>
                <h1>Masukkan kode OTP</h1>
                <p class="muted" style="margin-top:6px">Kode dikirim ke <strong>{{ $maskedEmail }}</strong>.</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="stack" data-once>
                @csrf
                <div class="field">
                    <label for="otp">Kode OTP</label>
                    <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                           maxlength="{{ config('sipasar.otp.length') }}" class="input @error('otp') is-invalid @enderror"
                           style="letter-spacing:.35em;font-weight:700;text-align:center;font-size:1.25rem" required autofocus>
                    @error('otp') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="password">Kata sandi baru</label>
                    <div class="input-group">
                        <input id="password" name="password" type="password" class="input @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
                        <button type="button" data-toggle-password="password" aria-pressed="false">Tampilkan</button>
                    </div>
                    <p class="hint">Minimal 8 karakter.</p>
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Ulangi kata sandi baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block" data-busy="Memproses…">Ganti kata sandi</button>
            </form>

            <p class="muted"><a href="{{ route('password.request') }}">Kirim ulang / ganti email</a></p>
        </div>
    </div>
@endsection
