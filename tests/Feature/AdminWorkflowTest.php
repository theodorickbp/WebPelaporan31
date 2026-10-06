<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function move(User $admin, Report $report, ReportStatus $to, ?string $note = null)
    {
        return $this->actingAs($admin)->post(route('admin.reports.transition', $report), [
            'status' => $to->value,
            'note' => $note,
        ]);
    }

    public function test_admin_walks_a_report_through_the_full_flow_and_student_is_notified(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $report = Report::factory()->create(['user_id' => $student->id]);

        $this->move($admin, $report, ReportStatus::Pending)->assertSessionHasNoErrors();
        $this->assertSame(ReportStatus::Pending, $report->fresh()->status);

        $this->move($admin, $report, ReportStatus::InRepair)->assertSessionHasNoErrors();
        $this->assertSame(ReportStatus::InRepair, $report->fresh()->status);

        $this->move($admin, $report, ReportStatus::Done, 'Kabel sudah diganti.')->assertSessionHasNoErrors();

        $fresh = $report->fresh();
        $this->assertSame(ReportStatus::Done, $fresh->status);
        $this->assertNotNull($fresh->resolved_at);
        $this->assertSame('Kabel sudah diganti.', $fresh->admin_note);
        $this->assertCount(3, $fresh->logs);

        Notification::assertSentToTimes($student, ReportStatusChanged::class, 3);
    }

    public function test_declining_requires_a_note(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create();

        $this->move($admin, $report, ReportStatus::Declined)->assertSessionHasErrors('note');
        $this->assertSame(ReportStatus::Submitted, $report->fresh()->status);

        $this->move($admin, $report, ReportStatus::Declined, 'Foto tidak jelas, tolong kirim ulang.')
            ->assertSessionHasNoErrors();
        $this->assertSame(ReportStatus::Declined, $report->fresh()->status);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create();

        $this->move($admin, $report, ReportStatus::Done)->assertSessionHasErrors('status');
        $this->move($admin, $report, ReportStatus::InRepair)->assertSessionHasErrors('status');
        $this->assertSame(ReportStatus::Submitted, $report->fresh()->status);

        $final = Report::factory()->status(ReportStatus::Done)->create();
        $this->move($admin, $final, ReportStatus::Pending)->assertSessionHasErrors('status');
    }

    public function test_student_cannot_change_status(): void
    {
        $student = User::factory()->create();
        $report = Report::factory()->create(['user_id' => $student->id]);

        $this->actingAs($student)
            ->post(route('admin.reports.transition', $report), ['status' => 'pending'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(ReportStatus::Submitted, $report->fresh()->status);
    }

    public function test_admin_dashboard_index_and_detail_render(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create(['title' => 'Kabel LAN putus']);

        $this->actingAs($admin)->get(route('admin.home'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee('Kabel LAN putus');
        $this->actingAs($admin)->get(route('admin.reports.index', ['status' => 'all', 'q' => 'LAN']))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.show', $report))->assertOk()->assertSee($report->code);
    }

    public function test_notification_opens_the_related_report_and_marks_it_read(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $report = Report::factory()->create(['user_id' => $student->id]);

        $this->move($admin, $report, ReportStatus::Pending);

        $notification = $student->notifications()->firstOrFail();
        $this->assertNull($notification->read_at);

        $this->actingAs($student)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('student.reports.show', $report));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
