<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

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

        // Akun yang belum disetujui: beri tahu alasannya, tapi HANYA kalau kata sandinya benar
        // (supaya orang lain tidak bisa mengintip status akun seseorang lewat tebak-tebak email).
        $candidate = User::where('email', $credentials['email'])->first();

        if ($candidate && ! $candidate->isApproved() && Hash::check($credentials['password'], $candidate->password)) {
            throw ValidationException::withMessages([
                'email' => $this->notApprovedMessage($candidate),
            ]);
        }

        $attempt = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'account_status' => AccountStatus::Approved->value,
        ];

        if (! Auth::attempt($attempt, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak cocok.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Pengajuan akun untuk Siswa atau Guru. Akun dibuat berstatus "pending" dan TIDAK langsung
     * login: baru bisa masuk setelah disetujui Admin/Toolman. Role hanya boleh student/teacher
     * (admin tidak pernah bisa dipilih dari request).
     */
    public function register(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);
        $request->merge([
            'nomor_induk' => preg_replace('/\s+/', '', (string) $request->input('nomor_induk')),
            'phone' => preg_replace('/[\s\-().]+/', '', (string) $request->input('phone')),
        ]);

        $isTeacher = $request->input('role') === UserRole::Teacher->value;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in([UserRole::Student->value, UserRole::Teacher->value])],
            'nomor_induk' => [
                'required', 'string', 'regex:/^[0-9]+$/',
                $isTeacher ? 'between:8,20' : 'size:10',
                'unique:users,nomor_induk',
            ],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'reason' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'role.required' => 'Pilih Siswa atau Guru.',
            'role.in' => 'Pilih Siswa atau Guru.',
            'nomor_induk.regex' => 'NISN/NIP hanya boleh berisi angka.',
            'nomor_induk.size' => 'NISN harus 10 digit.',
            'nomor_induk.between' => 'NIP/NUPTK harus 8 sampai 20 digit.',
            'nomor_induk.unique' => 'NISN/NIP ini sudah pernah diajukan. Kalau itu milikmu, hubungi Toolman.',
            'phone.regex' => 'Nomor telepon tidak valid. Contoh: 081234567890.',
        ]);

        $user = new User(Arr::only($data, ['name', 'email', 'phone', 'password']));
        $user->forceFill([
            'role' => UserRole::from($data['role']),
            'nomor_induk' => $data['nomor_induk'],
            'registration_reason' => $data['reason'] ?? null,
            'account_status' => AccountStatus::Pending,
        ])->save();

        // Kabari Toolman. Kalau pengiriman gagal (mis. SMTP), pengajuan tetap tersimpan.
        try {
            User::where('role', UserRole::Admin->value)->first()?->notify(new AccountRequested($user));
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()->route('login')
            ->with('notice', 'Pengajuan akun terkirim. Akun anda akan aktif setelah disetujui Admin.');
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

    private function notApprovedMessage(User $user): string
    {
        if ($user->account_status === AccountStatus::Rejected) {
            $reason = filled($user->rejection_reason)
                ? ' Alasan: '.rtrim((string) $user->rejection_reason, '.').'.'
                : '';

            return 'Pengajuan akunmu ditolak.'.$reason.' Hubungi Toolman kalau ini keliru.';
        }

        return 'Akunmu masih menunggu persetujuan Admin. Kamu bisa masuk setelah disetujui.';
    }
}
