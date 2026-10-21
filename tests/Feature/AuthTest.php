<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_public(): void
    {
        $this->get('/')->assertOk()->assertSee('SI-PASAR');
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'Budi@Example.test',
            'role' => 'student',
            'nomor_induk' => '1234567890',
            'phone' => '0812-3456-7890',
            'reason' => 'Siswa baru kelas X',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    public function test_registration_creates_a_pending_account_and_does_not_log_in(): void
    {
        $this->post('/daftar', $this->registrationPayload())->assertRedirect(route('login'));

        $this->assertGuest();

        $user = User::where('email', 'budi@example.test')->firstOrFail();
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertSame(AccountStatus::Pending, $user->account_status);
        $this->assertSame('1234567890', $user->nomor_induk);
        $this->assertSame('081234567890', $user->phone);
        $this->assertSame('Siswa baru kelas X', $user->registration_reason);
    }

    public function test_registration_rejects_a_forged_admin_role(): void
    {
        $this->from('/daftar')
            ->post('/daftar', $this->registrationPayload(['role' => 'admin']))
            ->assertRedirect('/daftar')
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'budi@example.test']);
    }

    public function test_only_one_admin_can_exist(): void
    {
        User::factory()->admin()->create();

        $this->expectException(DomainException::class);
        User::factory()->admin()->create();
    }

    public function test_existing_admin_can_still_be_updated(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->update(['name' => 'Pak Toolman']);

        $this->assertSame('Pak Toolman', $admin->fresh()->name);
    }

    public function test_login_and_role_based_redirect(): void
    {
        $student = User::factory()->create(['email' => 'siswa@example.test']);
        $admin = User::factory()->admin()->create(['email' => 'toolman@example.test']);

        $this->post('/masuk', ['email' => 'siswa@example.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertRedirect(route('student.home'));

        $this->post('/keluar');

        $this->post('/masuk', ['email' => 'toolman@example.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertRedirect(route('admin.home'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'siswa@example.test']);

        $this->from('/masuk')
            ->post('/masuk', ['email' => 'siswa@example.test', 'password' => 'salah-banget'])
            ->assertRedirect('/masuk')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_student_cannot_open_admin_area_and_guest_is_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_cannot_open_student_area(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/siswa')
            ->assertRedirect(route('dashboard'));
    }

    public function test_users_can_update_their_own_profile(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->put('/profil', ['name' => 'Nama Baru', 'email' => 'baru@example.test', 'kelas' => 'XII TKJ 2'])
            ->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('Nama Baru', $student->name);
        $this->assertSame('XII TKJ 2', $student->kelas);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->put('/profil', ['name' => 'Toolman Baru', 'email' => 'toolman-baru@example.test'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Toolman Baru', $admin->fresh()->name);
    }
}
