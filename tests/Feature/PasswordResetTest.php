<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\OtpPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function requestOtp(string $email)
    {
        return $this->post(route('password.email'), ['email' => $email]);
    }

    public function test_student_can_reset_password_with_correct_otp(): void
    {
        Notification::fake();

        $student = User::factory()->create(['email' => 'siswa@example.test']);

        $this->requestOtp('siswa@example.test')->assertRedirect(route('password.otp'));

        Notification::assertSentTo($student, OtpPasswordReset::class, function ($n) use ($student) {
            $record = PasswordResetOtp::where('user_id', $student->id)->first();

            return Hash::check($n->otp, $record->otp_hash);
        });

        $otp = null;
        Notification::assertSentTo($student, OtpPasswordReset::class, function ($n) use (&$otp) {
            $otp = $n->otp;

            return true;
        });

        $this->post(route('password.update'), [
            'otp' => $otp,
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('sandi-baru-123', $student->fresh()->password));
        $this->assertDatabaseCount('password_reset_otps', 0);

        // OTP sekali pakai: sesi sudah dibersihkan, request kedua ke halaman verifikasi ditolak.
        $this->get(route('password.otp'))->assertRedirect(route('password.request'));
    }

    public function test_wrong_otp_is_rejected_and_locks_after_max_attempts(): void
    {
        Notification::fake();
        $student = User::factory()->create(['email' => 'siswa@example.test']);
        $this->requestOtp('siswa@example.test');

        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('password.update'), [
                'otp' => '000000',
                'password' => 'sandi-baru-123',
                'password_confirmation' => 'sandi-baru-123',
            ])->assertSessionHasErrors('otp');
        }

        $this->assertDatabaseCount('password_reset_otps', 0); // sudah dihapus setelah percobaan maksimal
        $this->assertTrue(Hash::check('password', $student->fresh()->password)); // kata sandi lama tidak berubah
    }

    public function test_admin_email_never_receives_or_can_use_otp(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create(['email' => 'toolman@example.test']);

        $this->requestOtp('toolman@example.test')->assertRedirect(route('password.otp'));

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_otps', 0);

        $this->post(route('password.update'), [
            'otp' => '123456',
            'password' => 'coba-ganti-123',
            'password_confirmation' => 'coba-ganti-123',
        ])->assertSessionHasErrors('otp');

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }

    public function test_unknown_email_gives_same_neutral_response_as_known_email(): void
    {
        Notification::fake();

        $known = $this->requestOtp('memang-ada@example.test');
        $unknown = $this->requestOtp('tidak-pernah-daftar@example.test');

        $known->assertSessionHas('toast');
        $unknown->assertSessionHas('toast');
        $this->assertSame($known->getSession()->get('toast'), $unknown->getSession()->get('toast'));
    }

    public function test_verify_page_redirects_without_a_pending_request(): void
    {
        $this->get(route('password.otp'))->assertRedirect(route('password.request'));
    }
}
