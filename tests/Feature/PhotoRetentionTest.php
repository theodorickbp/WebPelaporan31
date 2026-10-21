<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportPhoto;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function reportWithPhoto(ReportStatus $status, $resolvedAt): Report
    {
        $report = Report::factory()->status($status)->create(['resolved_at' => $resolvedAt]);

        Storage::disk('local')->put("reports/{$report->id}/a.jpg", 'fake-image-bytes');
        $photo = new ReportPhoto(['path' => "reports/{$report->id}/a.jpg", 'sort' => 0]);
        $photo->report()->associate($report);
        $photo->save();

        return $report;
    }

    public function test_photos_are_purged_only_for_done_or_declined_past_retention(): void
    {
        Storage::fake('local');
        config(['sipasar.photo_retention_days' => 7]);

        $tooOldDone = $this->reportWithPhoto(ReportStatus::Done, now()->subDays(8));
        $tooOldDeclined = $this->reportWithPhoto(ReportStatus::Declined, now()->subDays(10));
        $recentDone = $this->reportWithPhoto(ReportStatus::Done, now()->subDays(2));
        $stillSubmitted = $this->reportWithPhoto(ReportStatus::Submitted, null);
        $oldButInRepair = $this->reportWithPhoto(ReportStatus::InRepair, null);

        $service = app(ReportService::class);
        $purged = $service->purgePhotosPastRetention();

        $this->assertSame(2, $purged);

        // Foto-nya hilang...
        $this->assertCount(0, $tooOldDone->fresh()->photos);
        $this->assertCount(0, $tooOldDeclined->fresh()->photos);
        Storage::disk('local')->assertMissing("reports/{$tooOldDone->id}/a.jpg");

        // ...tapi baris laporan & datanya tetap ada selamanya.
        $this->assertModelExists($tooOldDone);
        $this->assertSame(ReportStatus::Done, $tooOldDone->fresh()->status);

        // Yang belum lewat retensi, atau belum di status akhir, tidak disentuh sama sekali.
        $this->assertCount(1, $recentDone->fresh()->photos);
        $this->assertCount(1, $stillSubmitted->fresh()->photos);
        $this->assertCount(1, $oldButInRepair->fresh()->photos);
    }

    public function test_declined_transition_also_sets_resolved_at(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create();

        app(ReportService::class)->transition($report, ReportStatus::Declined, $admin, 'Foto tidak jelas.');

        $this->assertNotNull($report->fresh()->resolved_at);
    }

    public function test_was_photo_purged_is_false_for_a_report_that_still_has_its_photo(): void
    {
        Storage::fake('local');
        $withPhoto = $this->reportWithPhoto(ReportStatus::Done, now()->subDays(30));
        $this->assertFalse($withPhoto->wasPhotoPurged());

        app(ReportService::class)->purgePhotosPastRetention();
        $this->assertTrue($withPhoto->fresh()->wasPhotoPurged());
    }

    public function test_detail_pages_show_a_note_when_photo_was_purged(): void
    {
        Storage::fake('local');
        config(['sipasar.photo_retention_days' => 7]);

        $student = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $report = $this->reportWithPhoto(ReportStatus::Done, now()->subDays(30));
        $report->update(['user_id' => $student->id]);

        app(ReportService::class)->purgePhotosPastRetention();

        $this->actingAs($student)->get(route('student.reports.show', $report->fresh()))
            ->assertSeeText('Foto bukti sudah dihapus otomatis');

        $this->actingAs($admin)->get(route('admin.reports.show', $report->fresh()))
            ->assertSeeText('Foto bukti sudah dihapus otomatis');
    }

    public function test_purge_is_debounced_so_it_only_runs_once_within_the_cache_window(): void
    {
        Storage::fake('local');
        config(['sipasar.photo_retention_days' => 7]);
        $old = $this->reportWithPhoto(ReportStatus::Done, now()->subDays(30));

        $service = app(ReportService::class);
        $service->maybePurgePhotosPastRetention();
        $this->assertCount(0, $old->fresh()->photos);

        // Laporan baru yang juga sudah lewat retensi, tapi dalam jendela debounce yang sama -
        // TIDAK ikut dibersihkan sampai jendela cache-nya berakhir (ini cek jujur soal batasannya,
        // bukan klaim real-time).
        $another = $this->reportWithPhoto(ReportStatus::Done, now()->subDays(30));
        $service->maybePurgePhotosPastRetention();
        $this->assertCount(1, $another->fresh()->photos);
    }
}
