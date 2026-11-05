<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ToolmanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function submitReport(User $student, string $title = 'Proyektor tidak menyala')
    {
        return $this->actingAs($student)->post(route('student.reports.store'), [
            'category' => 'projector',
            'title' => $title,
            'location' => 'Lab RPL 1',
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ]);
    }

    public function test_toolman_accounts_exist_after_migration(): void
    {
        foreach (['toolmana@gmail.com', 'toolmanb@gmail.com', 'toolmanc@gmail.com'] as $email) {
            $this->assertTrue(User::where('email', $email)->where('role', 'toolman')->exists(), $email);
        }

        $this->post(route('login.attempt'), ['email' => 'toolmana@gmail.com', 'password' => 'toolman123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_toolman_only_sees_and_works_on_assigned_reports(): void
    {
        $a = User::factory()->toolman()->create();
        $b = User::factory()->toolman()->create();
        $mine = Report::factory()->status(ReportStatus::InRepair)->create(['toolman_id' => $a->id, 'title' => 'Tugas saya']);
        $other = Report::factory()->status(ReportStatus::InRepair)->create(['toolman_id' => $b->id, 'title' => 'Tugas orang lain']);

        $this->actingAs($a)->get(route('toolman.home'))->assertOk()->assertSee('Tugas saya')->assertDontSee('Tugas orang lain');
        $this->actingAs($a)->get(route('toolman.reports.show', $mine))->assertOk();
        $this->actingAs($a)->get(route('toolman.reports.show', $other))->assertForbidden();
        $this->actingAs($a)->post(route('toolman.reports.submit', $other), ['note' => 'Coba curang ya'])->assertForbidden();
        $this->assertSame(ReportStatus::InRepair, $other->fresh()->status);
    }

    public function test_toolman_cannot_use_admin_or_student_areas(): void
    {
        $t = User::factory()->toolman()->create();

        $this->actingAs($t)->get(route('admin.home'))->assertRedirect(route('dashboard'));
        $this->actingAs($t)->get(route('student.home'))->assertRedirect(route('dashboard'));
        $this->actingAs($t)->get(route('dashboard'))->assertRedirect(route('toolman.home'));
        $this->actingAs($t)->get(route('profile.edit'))->assertOk();
    }

    public function test_student_and_admin_cannot_use_toolman_area(): void
    {
        $this->actingAs(User::factory()->create())->get(route('toolman.home'))->assertRedirect(route('dashboard'));
        $this->actingAs(User::factory()->admin()->create())->get(route('toolman.home'))->assertRedirect(route('dashboard'));
    }

    public function test_toolman_result_requires_a_note(): void
    {
        $t = User::factory()->toolman()->create();
        $r = Report::factory()->status(ReportStatus::InRepair)->create(['toolman_id' => $t->id]);

        $this->actingAs($t)->post(route('toolman.reports.submit', $r), ['note' => ''])->assertSessionHasErrors('note');
        $this->assertSame(ReportStatus::InRepair, $r->fresh()->status);
    }

    public function test_assignment_requires_a_valid_toolman(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $r = Report::factory()->status(ReportStatus::Pending)->create();

        $this->actingAs($admin)->post(route('admin.reports.assign', $r), [])->assertSessionHasErrors('toolman_id');
        $this->actingAs($admin)->post(route('admin.reports.assign', $r), ['toolman_id' => $student->id])->assertSessionHasErrors('toolman_id');
        $this->assertSame(ReportStatus::Pending, $r->fresh()->status);
    }

    public function test_admin_can_return_result_to_toolman_with_a_note(): void
    {
        $admin = User::factory()->admin()->create();
        $t = User::factory()->toolman()->create();
        $r = Report::factory()->status(ReportStatus::Review)->create(['toolman_id' => $t->id]);

        $this->actingAs($admin)->post(route('admin.reports.transition', $r), ['status' => 'in_repair'])->assertSessionHasErrors('note');
        $this->actingAs($admin)->post(route('admin.reports.transition', $r), ['status' => 'in_repair', 'note' => 'Masih mati, cek lagi.'])
            ->assertSessionHasNoErrors();
        $this->assertSame(ReportStatus::InRepair, $r->fresh()->status);
    }

    public function test_admin_cannot_jump_from_approved_to_in_repair_without_choosing_toolman(): void
    {
        $admin = User::factory()->admin()->create();
        $r = Report::factory()->status(ReportStatus::Pending)->create();

        $this->actingAs($admin)->post(route('admin.reports.transition', $r), ['status' => 'in_repair', 'note' => 'x'])
            ->assertSessionHasErrors('status');
        $this->assertSame(ReportStatus::Pending, $r->fresh()->status);
    }

    public function test_admin_can_approve_at_most_15_reports_at_once(): void
    {
        $admin = User::factory()->admin()->create();
        Report::factory()->count(15)->status(ReportStatus::InRepair)->create();
        $next = Report::factory()->create();

        $this->actingAs($admin)->post(route('admin.reports.transition', $next), ['status' => 'pending'])
            ->assertSessionHasErrors('status');
        $this->assertSame(ReportStatus::Submitted, $next->fresh()->status);

        // Satu selesai -> slot kosong
        Report::where('status', 'in_repair')->first()->update(['status' => ReportStatus::Done]);
        $this->actingAs($admin)->post(route('admin.reports.transition', $next), ['status' => 'pending'])
            ->assertSessionHasNoErrors();
        $this->assertSame(ReportStatus::Pending, $next->fresh()->status);
    }

    public function test_reports_beyond_50_wait_in_a_queue_and_are_promoted_in_order(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->admin()->create();
        Report::factory()->count(50)->create(['created_at' => now()->subDay()]);

        $s1 = User::factory()->create();
        $s2 = User::factory()->create();
        $this->submitReport($s1, 'Laporan ke-51')->assertSessionHasNoErrors();
        $this->travel(1)->minutes();
        $this->submitReport($s2, 'Laporan ke-52')->assertSessionHasNoErrors();

        $first = Report::where('title', 'Laporan ke-51')->firstOrFail();
        $second = Report::where('title', 'Laporan ke-52')->firstOrFail();
        $this->assertSame(ReportStatus::Waiting, $first->status);
        $this->assertSame(ReportStatus::Waiting, $second->status);
        $this->assertSame(50, Report::where('status', 'submitted')->count());

        // Admin memproses 1 laporan -> hanya yang paling lama menunggu yang naik.
        $this->actingAs($admin)->post(route('admin.reports.transition', Report::where('status', 'submitted')->oldest()->first()), ['status' => 'pending']);

        $this->assertSame(ReportStatus::Submitted, $first->fresh()->status);
        $this->assertSame(ReportStatus::Waiting, $second->fresh()->status);
        $this->assertSame(50, Report::where('status', 'submitted')->count());
    }

    public function test_cancelling_a_queued_report_promotes_a_waiting_one(): void
    {
        Storage::fake('local');

        Report::factory()->count(49)->create();
        $student = User::factory()->create();
        $this->submitReport($student, 'Yang ke-50');
        $mine = Report::where('title', 'Yang ke-50')->firstOrFail();
        $this->assertSame(ReportStatus::Submitted, $mine->status);

        $other = User::factory()->create();
        $this->submitReport($other, 'Yang ke-51');
        $waiting = Report::where('title', 'Yang ke-51')->firstOrFail();
        $this->assertSame(ReportStatus::Waiting, $waiting->status);

        $this->actingAs($student)->delete(route('student.reports.destroy', $mine))->assertRedirect();
        $this->assertSame(ReportStatus::Submitted, $waiting->fresh()->status);
    }

    public function test_student_can_create_at_most_5_reports_per_day_even_after_cancelling(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            $this->submitReport($student, "Laporan nomor {$i}")->assertSessionHasNoErrors();
        }
        $this->assertSame(5, Report::where('user_id', $student->id)->count());

        $this->submitReport($student, 'Laporan nomor 6')->assertSessionHasErrors('limit');
        $this->assertSame(5, Report::where('user_id', $student->id)->count());

        // Membatalkan laporan tidak mengembalikan jatah.
        $this->actingAs($student)->delete(route('student.reports.destroy', Report::where('user_id', $student->id)->first()));
        $this->submitReport($student, 'Laporan nomor 7')->assertSessionHasErrors('limit');

        // Hari berikutnya jatah kembali.
        $this->travel(1)->days();
        $this->submitReport($student, 'Laporan besok')->assertSessionHasNoErrors();

        // Akun lain tidak terpengaruh.
        $this->submitReport(User::factory()->create(), 'Laporan siswa lain')->assertSessionHasNoErrors();
    }

    public function test_toolman_name_appears_in_history_for_everyone(): void
    {
        $admin = User::factory()->admin()->create();
        $t = User::factory()->toolman()->create(['name' => 'Toolman A']);
        $student = User::factory()->create();
        $r = Report::factory()->status(ReportStatus::Pending)->create(['user_id' => $student->id]);

        app(ReportService::class)->assign($r, $t, $admin);

        $this->actingAs($student)->get(route('student.reports.show', $r))->assertOk()->assertSee('Toolman A');
        $this->actingAs($admin)->get(route('admin.reports.show', $r))->assertOk()->assertSee('Toolman A');
        $this->actingAs($t)->get(route('toolman.reports.show', $r))->assertOk()->assertSee('Toolman A');
    }

    public function test_toolman_and_student_notifications_open_the_right_page(): void
    {
        $admin = User::factory()->admin()->create();
        $t = User::factory()->toolman()->create();
        $r = Report::factory()->status(ReportStatus::Pending)->create();

        app(ReportService::class)->assign($r, $t, $admin);

        $n = $t->notifications()->firstOrFail();
        $this->actingAs($t)->get(route('notifications.open', $n->id))->assertRedirect(route('toolman.reports.show', $r));
    }

    public function test_forms_mark_required_fields_with_a_red_asterisk(): void
    {
        $student = User::factory()->create();

        $this->get(route('register'))->assertSee('class="req"', false);
        $this->get(route('login'))->assertSee('class="req"', false);
        $this->actingAs($student)->get(route('student.reports.create'))->assertSee('class="req"', false);
        $this->actingAs($student)->get(route('profile.edit'))->assertSee('class="req"', false)->assertSee('NISN')->assertDontSee('NISN/NIP');
    }
}
