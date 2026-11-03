@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    <div class="page-head"><h1>Profil</h1></div>

    <div class="stack-lg">
        <form method="POST" action="{{ route('profile.update') }}" class="panel stack">
            @csrf
            @method('PUT')
            <h2>Data diri</h2>

            <div class="field">
                <label for="name">Nama</label>
                <input id="name" name="name" type="text" class="input @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required maxlength="80" autocomplete="name">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="email" inputmode="email">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @unless ($user->isAdmin())
                <div class="field">
                    <label for="kelas">Kelas</label>
                    <input id="kelas" name="kelas" type="text" class="input @error('kelas') is-invalid @enderror" value="{{ old('kelas', $user->kelas) }}" required maxlength="30">
                    @error('kelas') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endunless

            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
        </form>

        <form method="POST" action="{{ route('profile.password') }}" class="panel stack">
            @csrf
            @method('PUT')
            <h2>Ganti kata sandi</h2>

            <div class="field">
                <label for="current_password">Kata sandi saat ini</label>
                <input id="current_password" name="current_password" type="password" class="input" required autocomplete="current-password">
                @error('current_password', 'password') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="new_password">Kata sandi baru</label>
                <input id="new_password" name="password" type="password" class="input" required minlength="8" autocomplete="new-password">
                <p class="hint">Minimal 8 karakter. Perangkat lain akan keluar otomatis.</p>
                @error('password', 'password') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="new_password_confirmation">Ulangi kata sandi baru</label>
                <input id="new_password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">Ganti kata sandi</button>
        </form>

        <form method="POST" action="{{ route('profile.logout-others') }}" class="panel stack">
            @csrf
            <h2>Perangkat</h2>
            <p class="muted">
                Kamu bisa masuk di beberapa perangkat sekaligus.
                @if ($devices !== null) Sekarang ada {{ $devices }} sesi aktif. @endif
                Kalau HP hilang atau lupa keluar di komputer sekolah, keluarkan semua perangkat lain di sini.
            </p>

            <div class="field">
                <label for="devices_password">Kata sandi</label>
                <input id="devices_password" name="password" type="password" class="input" required autocomplete="current-password">
                @error('password', 'devices') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-soft">Keluarkan perangkat lain</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-block"><x-icon name="logout" :size="18" /> Keluar dari perangkat ini</button>
        </form>
    </div>
@endsection
