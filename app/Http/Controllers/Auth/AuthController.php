<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\AccountRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = $credentials['email'].'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak cocok.',
            ]);
        }

        RateLimiter::clear($key);

        // Akun yang belum disetujui tidak boleh memperoleh sesi login.
        $user = $request->user();
        if ($user->account_status !== AccountStatus::Approved) {
            Auth::logout();

            $reason = filled($user->rejection_reason)
                ? ' Alasan: '.rtrim((string) $user->rejection_reason, '.').'.'
                : '';
            $message = $user->account_status === AccountStatus::Rejected
                ? 'Pengajuan akunmu ditolak.'.$reason.' Hubungi Toolman kalau ini keliru.'
                : 'Akunmu masih menunggu persetujuan Admin. Kamu bisa masuk setelah disetujui.';

            throw ValidationException::withMessages(['email' => $message]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /** Pendaftaran HANYA untuk siswa. Role tidak pernah dibaca dari request. */
    public function register(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'kelas' => ['required', 'string', 'max:30'],
            'nomor_induk' => ['required', 'digits:10', 'unique:users,nomor_induk'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+().\s-]{8,20}$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nomor_induk.digits' => 'NISN harus terdiri dari tepat 10 angka.',
            'nomor_induk.unique' => 'NISN tersebut sudah terdaftar.',
            'phone.regex' => 'Masukkan nomor telepon yang valid.',
        ]);

        // Role, NISN, dan status diisi secara eksplisit oleh server; tidak pernah
        // menerima role/status mentah dari request.
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'kelas' => $data['kelas'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);
        $user->forceFill([
            'nomor_induk' => $data['nomor_induk'],
            'account_status' => AccountStatus::Pending,
        ])->save();

        try {
            User::query()->where('role', UserRole::Admin->value)->first()?->notify(new AccountRequested($user));
        } catch (Throwable $e) {
            report($e); // Pengajuan tetap tersimpan meski notifikasi gagal.
        }

        return redirect()->route('login')->with('status', 'Pendaftaran berhasil dikirim. Akunmu dapat digunakan setelah disetujui Admin/Toolman.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function normalizeEmail(Request $request): void
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
    }
}
