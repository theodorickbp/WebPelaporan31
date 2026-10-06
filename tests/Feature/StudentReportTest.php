<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportPhoto;
use App\Models\User;
use App\Notifications\ReportSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentReportTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'category' => 'projector',
            'title' => 'Proyektor tidak menyala',
            'location' => 'Lab RPL 2',
            'description' => 'Lampu indikator berkedip merah.',
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 800, 600)],
        ], $override);
    }

    public function test_student_can_submit_a_report_with_photo(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $response = $this->actingAs($student)->post(route('student.reports.store'), $this->payload());

        $report = Report::firstOrFail();
        $response->assertRedirect(route('student.reports.show', $report));

        $this->assertSame(ReportStatus::Submitted, $report->status);
        $this->assertSame($student->id, $report->user_id);
        $this->assertCount(1, $report->photos);
        $this->assertCount(1, $report->logs);
        Storage::disk('local')->assertExists($report->photos->first()->path);

        Notification::assertSentTo($admin, ReportSubmitted::class);
    }

    public function test_photo_is_required(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post(route('student.reports.store'), $this->payload(['photos' => []]))
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post(route('student.reports.store'), $this->payload([
                'photos' => [UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf')],
            ]))
            ->assertSessionHasErrors('photos.0');
    }

    public function test_admin_cannot_submit_reports(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('student.reports.store'), $this->payload())
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_student_only_sees_own_reports(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $report = Report::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->get(route('student.reports.show', $report))->assertOk()->assertSee($report->title);
        $this->actingAs($other)->get(route('student.reports.show', $report))->assertForbidden();
    }

    public function test_photo_access_is_limited_to_owner_and_admin(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create(['user_id' => $owner->id]);

        Storage::disk('local')->put("reports/{$report->id}/a.jpg", 'fake-image-bytes');
        $photo = new ReportPhoto(['path' => "reports/{$report->id}/a.jpg", 'sort' => 0]);
        $photo->report()->associate($report);
        $photo->save();

        $this->actingAs($owner)->get(route('photos.show', $photo))->assertOk();
        $this->actingAs($admin)->get(route('photos.show', $photo))->assertOk();
        $this->actingAs($other)->get(route('photos.show', $photo))->assertForbidden();
    }

    public function test_student_can_cancel_only_while_submitted(): void
    {
        Storage::fake('local');

        $student = User::factory()->create();
        $open = Report::factory()->create(['user_id' => $student->id]);
        $reviewed = Report::factory()->status(ReportStatus::Pending)->create(['user_id' => $student->id]);

        $this->actingAs($student)
            ->delete(route('student.reports.destroy', $open))
            ->assertRedirect(route('student.reports.index'));
        $this->assertModelMissing($open);

        $this->actingAs($student)->delete(route('student.reports.destroy', $reviewed));
        $this->assertModelExists($reviewed);
    }

    public function test_history_lists_reports_and_filters(): void
    {
        $student = User::factory()->create();
        Report::factory()->create(['user_id' => $student->id, 'title' => 'Laporan masih diproses']);
        Report::factory()->status(ReportStatus::Done)->create(['user_id' => $student->id, 'title' => 'Laporan sudah beres']);

        $this->actingAs($student)->get(route('student.reports.index', ['filter' => 'done']))
            ->assertOk()
            ->assertSee('Laporan sudah beres')
            ->assertDontSee('Laporan masih diproses');
    }
}
