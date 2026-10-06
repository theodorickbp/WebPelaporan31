<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\OtpPasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Lupa kata sandi KHUSUS siswa, lewat kode OTP ke email.
 *
 * Admin/Toolman sengaja tidak bisa lewat sini: hanya 1 akun, dan kalau lupa,
 * penggantian dilakukan manual oleh pengelola sekolah (lewat seeder/database),
 * bukan lewat form publik - supaya akun admin tidak jadi sasaran tebak-tebak email.
 *
 * Respons ke pemakai selalu seragam ("kalau email itu ... sudah dikirim") supaya
 * tidak membocorkan apakah sebuah email terdaftar atau itu email si admin.
 */
class PasswordResetController extends Controller
{
    private const NEUTRAL_MESSAGE = 'Kalau email itu terdaftar sebagai akun siswa, kode OTP sudah dikirim.';

    public function showRequest(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower(trim($data['email']));

        $user = User::where('email', $email)->where('role', UserRole::Student->value)->first();

        if ($user) {
            $length = (int) config('sipasar.otp.length');
            $otp = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

            PasswordResetOtp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'otp_hash' => Hash::make($otp),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(config('sipasar.otp.expires_minutes')),
                ]
            );

            $user->notify(new OtpPasswordReset($otp, config('sipasar.otp.expires_minutes')));
        }

        // Email disimpan di sesi (bukan rahasia, siswa sendiri yang mengetik) supaya
        // langkah verifikasi berikutnya tahu siapa yang divalidasi, tanpa menaruh apa pun di URL.
        $request->session()->put('password_reset.email', $email);

        return redirect()->route('password.otp')->with('toast', self::NEUTRAL_MESSAGE);
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset.email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-otp', ['maskedEmail' => $this->mask($email)]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset.email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'otp' => ['required', 'string', 'size:'.config('sipasar.otp.length')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $email)->where('role', UserRole::Student->value)->first();
        $record = $user ? PasswordResetOtp::where('user_id', $user->id)->first() : null;

        $genericFail = fn (string $msg) => back()->withErrors(['otp' => $msg])->withInput();

        if (! $user || ! $record || $record->isExpired() || ! $record->hasAttemptsLeft()) {
            $record?->delete();

            return $genericFail('Kode OTP salah, sudah kedaluwarsa, atau sudah tidak berlaku. Minta kode baru.');
        }

        if (! Hash::check($data['otp'], $record->otp_hash)) {
            $record->increment('attempts');

            if (! $record->hasAttemptsLeft()) {
                $record->delete();

                return $genericFail('Terlalu banyak kode salah. Minta kode OTP baru.');
            }

            $left = config('sipasar.otp.max_attempts') - $record->attempts;

            return $genericFail("Kode OTP salah. Sisa percobaan: {$left}.");
        }

        DB::transaction(function () use ($user, $data, $record) {
            $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
            $record->delete();

            // Perangkat lain (termasuk yang mungkin dipakai orang lain) ikut dikeluarkan.
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
        });

        $request->session()->forget('password_reset.email');

        return redirect()->route('login')->with('toast', 'Kata sandi berhasil diganti. Silakan masuk dengan kata sandi baru.');
    }

    private function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('•', max(mb_strlen($local) - mb_strlen($visible), 1)).'@'.$domain;
    }
}
