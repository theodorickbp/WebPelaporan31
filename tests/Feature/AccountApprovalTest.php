<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\AccountRequested;
use App\Notifications\AccountReviewed;
use App\Notifications\OtpPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Calon Pengguna',
            'email' => 'calon@example.test',
            'role' => 'student',
            'nomor_induk' => '0012345678',
            'phone' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    // ---------- Pengajuan akun ----------

    public function test_teacher_can_apply_with_nip(): void
    {
        $this->post('/daftar', $this->payload([
            'email' => 'guru@example.test',
            'role' => 'teacher',
            'nomor_induk' => '198001012005011001',
        ]))->assertRedirect(route('login'));

        $user = User::where('email', 'guru@example.test')->firstOrFail();
        $this->assertSame(UserRole::Teacher, $user->role);
        $this->assertSame(AccountStatus::Pending, $user->account_status);
    }

    public function test_reason_is_optional(): void
    {
        $this->post('/daftar', $this->payload())->assertSessionHasNoErrors();

        $this->assertNull(User::where('email', 'calon@example.test')->firstOrFail()->registration_reason);
    }

    public function test_nisn_must_be_exactly_10_digits_for_students(): void
    {
        $this->post('/daftar', $this->payload(['nomor_induk' => '12345']))
            ->assertSessionHasErrors('nomor_induk');

        $this->post('/daftar', $this->payload(['nomor_induk' => '12345abcde']))
            ->assertSessionHasErrors('nomor_induk');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_phone_is_required_and_must_look_like_a_phone_number(): void
    {
        $this->post('/daftar', $this->payload(['phone' => '']))->assertSessionHasErrors('phone');
        $this->post('/daftar', $this->payload(['phone' => 'abc']))->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_nomor_induk_and_email_are_rejected(): void
    {
        User::factory()->create(['nomor_induk' => '0012345678', 'email' => 'ada@example.test']);

        $this->post('/daftar', $this->payload())->assertSessionHasErrors('nomor_induk');
        $this->post('/daftar', $this->payload(['nomor_induk' => '0099999999', 'email' => 'ada@example.test']))
            ->assertSessionHasErrors('email');
    }

    public function test_admin_is_notified_of_a_new_application(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->post('/daftar', $this->payload());

        Notification::assertSentTo($admin, AccountRequested::class);
    }

    // ---------- Login ----------

    public function test_pending_account_cannot_log_in(): void
    {
        User::factory()->pending()->create(['email' => 'baru@example.test']);

        $this->from('/masuk')
            ->post('/masuk', ['email' => 'baru@example.test', 'password' => 'password'])
            ->assertRedirect('/masuk')
            ->assertSessionHasErrors(['email' => 'Akunmu masih menunggu persetujuan Admin. Kamu bisa masuk setelah disetujui.']);

        $this->assertGuest();
    }

    public function test_rejected_account_cannot_log_in_and_sees_the_reason(): void
    {
        User::factory()->rejected()->create(['email' => 'tolak@example.test']);

        $this->from('/masuk')
            ->post('/masuk', ['email' => 'tolak@example.test', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Pengajuan akunmu ditolak. Alasan: Data tidak cocok. Hubungi Toolman kalau ini keliru.']);

        $this->assertGuest();
    }

    public function test_wrong_password_on_pending_account_does_not_reveal_its_status(): void
    {
        User::factory()->pending()->create(['email' => 'baru@example.test']);

        $this->from('/masuk')
            ->post('/masuk', ['email' => 'baru@example.test', 'password' => 'salah-banget'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak cocok.']);

        $this->assertGuest();
    }

    // ---------- Keputusan Toolman ----------

    public function test_admin_can_approve_and_the_user_can_then_log_in(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->pending()->create(['email' => 'baru@example.test']);

        $this->actingAs($admin)
            ->post(route('admin.accounts.approve', $user))
            ->assertSessionHas('toast');

        $user->refresh();
        $this->assertSame(AccountStatus::Approved, $user->account_status);
        $this->assertNotNull($user->reviewed_at);
        Notification::assertSentTo($user, AccountReviewed::class, fn ($n) => $n->approved === true);

        $this->post('/keluar');

        $this->post('/masuk', ['email' => 'baru@example.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_reject_with_a_reason(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->pending()->create();

        $this->actingAs($admin)
            ->post(route('admin.accounts.reject', $user), ['reason' => 'NISN tidak ditemukan'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame(AccountStatus::Rejected, $user->account_status);
        $this->assertSame('NISN tidak ditemukan', $user->rejection_reason);
        Notification::assertSentTo($user, AccountReviewed::class, fn ($n) => $n->approved === false);
    }

    public function test_reason_is_optional_when_rejecting(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->pending()->create();

        $this->actingAs($admin)
            ->post(route('admin.accounts.reject', $user))
            ->assertSessionHasNoErrors();

        $this->assertSame(AccountStatus::Rejected, $user->fresh()->account_status);
        $this->assertNull($user->fresh()->rejection_reason);
    }

    public function test_only_pending_applications_can_be_rejected(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.accounts.reject', $user));

        $this->assertSame(AccountStatus::Approved, $user->fresh()->account_status);
    }

    public function test_a_rejected_application_can_be_approved_later(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->rejected()->create();

        $this->actingAs($admin)->post(route('admin.accounts.approve', $user));

        $user->refresh();
        $this->assertSame(AccountStatus::Approved, $user->account_status);
        $this->assertNull($user->rejection_reason);
    }

    public function test_admin_account_cannot_be_reviewed(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.accounts.approve', $admin))->assertNotFound();
    }

    public function test_non_admin_cannot_review_accounts(): void
    {
        $student = User::factory()->create();
        $pending = User::factory()->pending()->create();

        $this->actingAs($student)
            ->post(route('admin.accounts.approve', $pending))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(AccountStatus::Pending, $pending->fresh()->account_status);
    }

    public function test_admin_sees_pending_applications_on_the_accounts_page(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->pending()->create(['name' => 'Calon Siswa Baru']);

        $this->actingAs($admin)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee('Calon Siswa Baru')
            ->assertSee('Setujui');

        $this->actingAs($admin)
            ->get(route('admin.home'))
            ->assertOk()
            ->assertSee('pengajuan akun');
    }

    // ---------- Guru ----------

    public function test_teacher_uses_the_student_area_like_a_student(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/dashboard')->assertRedirect(route('student.home'));
        $this->actingAs($teacher)->get(route('student.home'))->assertOk();
        $this->actingAs($teacher)->get(route('student.reports.create'))->assertOk();
        $this->actingAs($teacher)->get('/admin')->assertRedirect(route('dashboard'));
    }

    public function test_teacher_can_update_phone_on_profile(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->put('/profil', ['name' => 'Bu Guru', 'email' => 'guru2@example.test', 'phone' => '0811-222-3333'])
            ->assertSessionHasNoErrors();

        $this->assertSame('08112223333', $teacher->fresh()->phone);
    }

    // ---------- Lupa kata sandi ----------

    public function test_teachers_can_reset_password_but_pending_accounts_cannot(): void
    {
        Notification::fake();
        $teacher = User::factory()->teacher()->create(['email' => 'guru@example.test']);
        $pending = User::factory()->pending()->create(['email' => 'baru@example.test']);

        $this->post(route('password.email'), ['email' => 'guru@example.test']);
        $this->post(route('password.email'), ['email' => 'baru@example.test']);

        Notification::assertSentTo($teacher, OtpPasswordReset::class);
        Notification::assertNotSentTo($pending, OtpPasswordReset::class);
    }
}
