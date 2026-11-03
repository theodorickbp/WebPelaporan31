<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Notifications\AccountRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_registration_waits_for_admin_approval(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->post(route('register.store'), [
            'name' => 'Siswa Baru',
            'kelas' => 'XII RPL C',
            'nomor_induk' => '0123456789',
            'phone' => '081234567890',
            'email' => 'siswa.baru@example.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('login'));

        $student = User::where('email', 'siswa.baru@example.test')->firstOrFail();
        $this->assertSame(AccountStatus::Pending, $student->account_status);
        $this->assertGuest();
        Notification::assertSentTo($admin, AccountRequested::class);

        $this->post(route('login.attempt'), [
            'email' => 'siswa.baru@example.test',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_approved_student_can_log_in(): void
    {
        $student = User::factory()->create([
            'account_status' => AccountStatus::Approved,
            'password' => 'rahasia123',
        ]);

        $this->post(route('login.attempt'), [
            'email' => $student->email,
            'password' => 'rahasia123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($student);
    }
}
