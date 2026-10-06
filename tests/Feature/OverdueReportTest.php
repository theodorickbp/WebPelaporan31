<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OverdueReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_is_overdue_only_when_still_submitted_past_the_configured_hours(): void
    {
        config(['sipasar.overdue_hours' => 48]);

        $fresh = Report::factory()->create(['created_at' => now()->subHours(10)]);
        $this->assertFalse($fresh->isOverdue());

        $old = Report::factory()->create(['created_at' => now()->subHours(49)]);
        $this->assertTrue($old->isOverdue());

        $oldButHandled = Report::factory()->status(ReportStatus::Pending)->create(['created_at' => now()->subHours(72)]);
        $this->assertFalse($oldButHandled->isOverdue());
    }

    public function test_overdue_badge_appears_on_admin_ticket_list(): void
    {
        $admin = User::factory()->admin()->create();
        Report::factory()->create(['title' => 'Laporan baru saja', 'created_at' => now()]);
        Report::factory()->create(['title' => 'Laporan lama terlantar', 'created_at' => now()->subHours(72)]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index', ['status' => 'submitted']));

        $response->assertOk();
        $response->assertSeeText('Menunggu lama, segera ditinjau');
    }

    public function test_status_counts_cache_is_invalidated_after_a_transition(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create();

        $this->actingAs($admin)->get(route('admin.home'))->assertSeeText('1'); // submitted = 1

        $this->actingAs($admin)->post(route('admin.reports.transition', $report), ['status' => ReportStatus::Pending->value]);

        $this->actingAs($admin)->get(route('admin.home'))->assertOk();
        $this->assertSame(ReportStatus::Pending, $report->fresh()->status);
    }
}
