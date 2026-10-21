@extends('layouts.public')

@section('title', 'Buat Akun')
@section('body-class', 'auth-page')

@section('content')
    @php $isTeacher = old('role', 'student') === 'teacher'; @endphp
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

        <div class="auth-main auth-main-wide">
            <x-brand />
            <div>
                <h1>Buat Akun</h1>
                <p class="muted" style="margin-top:6px">Isi data dirimu. Admin akan memeriksanya sebelum akun diaktifkan.</p>
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="stack">
                @csrf
                <div class="field">
                    <label for="name">Nama lengkap</label>
                    <input id="name" name="name" type="text" class="input @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="name" maxlength="80">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email" inputmode="email">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field-row field-row-id">
                    <div class="field">
                        <label for="nomor_induk">NISN/NIP</label>
                        <input id="nomor_induk" name="nomor_induk" type="text" class="input @error('nomor_induk') is-invalid @enderror"
                               value="{{ old('nomor_induk') }}" required inputmode="numeric" autocomplete="off"
                               maxlength="{{ $isTeacher ? 20 : 10 }}" placeholder="{{ $isTeacher ? 'NIP / NUPTK' : '10 digit NISN' }}">
                        @error('nomor_induk') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="select @error('role') is-invalid @enderror" required>
                            <option value="student" @selected(! $isTeacher)>Siswa</option>
                            <option value="teacher" @selected($isTeacher)>Guru</option>
                        </select>
                        @error('role') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="hint field-hint" id="nomor_induk_hint">{{ $isTeacher ? 'Guru: isi NIP (18 digit) atau NUPTK (16 digit).' : 'Siswa: isi NISN (10 digit).' }}</p>

                <div class="field">
                    <label for="phone">No. Telpon</label>
                    <input id="phone" name="phone" type="tel" class="input @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" maxlength="20" placeholder="Contoh: 081234567890">
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="reason">Alasan / Perihal pengajuan <span class="muted">(opsional)</span></label>
                    <textarea id="reason" name="reason" class="textarea @error('reason') is-invalid @enderror" maxlength="500" rows="3" placeholder="Contoh: Siswa baru kelas X RPL 1, ingin melaporkan kerusakan di lab.">{{ old('reason') }}</textarea>
                    @error('reason') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field-row">
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
                        <label for="password_confirmation">Konfirmasi kata sandi</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
                    </div>
                </div>

                <p class="form-note" role="note">Akun anda akan aktif setelah disetujui Admin.</p>

                <button type="submit" class="btn btn-primary btn-lg btn-block">Kirim Pengajuan</button>
            </form>

            <p class="muted">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div>
    </div>

    <script>
        // Menyesuaikan petunjuk NISN/NIP saat Role diganti (tanpa JS pun form tetap berfungsi).
        (function () {
            var role = document.getElementById('role');
            var field = document.getElementById('nomor_induk');
            var hint = document.getElementById('nomor_induk_hint');
            if (!role || !field || !hint) { return; }

            function sync() {
                var teacher = role.value === 'teacher';
                field.placeholder = teacher ? 'NIP / NUPTK' : '10 digit NISN';
                field.maxLength = teacher ? 20 : 10;
                hint.textContent = teacher
                    ? 'Guru: isi NIP (18 digit) atau NUPTK (16 digit).'
                    : 'Siswa: isi NISN (10 digit).';
            }

            role.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
