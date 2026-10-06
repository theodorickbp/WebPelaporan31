<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportStatusChanged;
use App\Notifications\ReportSubmitted;
use Illuminate\Support\Facades\Cache;
use App\Support\Thumbnailer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReportService
{
    private const DISK = 'local';

    /**
     * Membuat laporan beserta foto buktinya dalam satu transaksi.
     * Jika transaksi gagal, file yang sudah tersimpan ikut dihapus.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function create(User $user, array $data, array $photos): Report
    {
        $stored = [];

        try {
            $report = DB::transaction(function () use ($user, $data, $photos, &$stored) {
                $report = Report::create([
                    'code' => Report::generateCode(),
                    'user_id' => $user->id,
                    'category' => $data['category'],
                    'title' => $data['title'],
                    'location' => $data['location'],
                    'description' => $data['description'] ?? null,
                    'status' => ReportStatus::Submitted,
                ]);

                foreach (array_values($photos) as $i => $file) {
                    $path = $file->store("reports/{$report->id}", self::DISK);
                    $stored[] = $path;

                    $report->photos()->create([
                        'path' => $path,
                        'size' => $file->getSize(),
                        'sort' => $i,
                    ]);
                }

                $report->logs()->create([
                    'user_id' => $user->id,
                    'from_status' => null,
                    'to_status' => ReportStatus::Submitted,
                    'note' => null,
                ]);

                return $report;
            });
        } catch (Throwable $e) {
            foreach ($stored as $path) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $e;
        }

        Cache::forget('sipasar:report-status-counts');
        $this->makeThumbnails($report);
        $this->notifyAdmin($report);

        return $report;
    }

    /** Memindahkan laporan ke status berikutnya (hanya jalur yang diizinkan enum). */
    public function transition(Report $report, ReportStatus $to, User $actor, ?string $note = null): Report
    {
        $note = filled($note) ? trim($note) : null;

        DB::transaction(function () use (&$report, $to, $actor, $note) {
            $fresh = Report::whereKey($report->getKey())->lockForUpdate()->firstOrFail();

            if (! $fresh->status->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => 'Status laporan sudah berubah atau perubahan ini tidak diizinkan. Muat ulang halaman.',
                ]);
            }

            $from = $fresh->status;
            $fresh->status = $to;

            if ($note !== null) {
                $fresh->admin_note = $note;
            }
            if ($to === ReportStatus::Done) {
                $fresh->resolved_at = now();
            }
            $fresh->save();

            $fresh->logs()->create([
                'user_id' => $actor->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
            ]);

            $report = $fresh;
        });

        Cache::forget('sipasar:report-status-counts');
        $report->load('user');

        try {
            $report->user->notify(new ReportStatusChanged($report, $to, $note));
        } catch (Throwable $e) {
            report($e); // gagal kirim notifikasi tidak boleh membatalkan perubahan status
        }

        return $report;
    }

    /** Siswa membatalkan laporannya sendiri (hanya saat masih "menunggu ditinjau"). */
    public function cancel(Report $report): void
    {
        DB::transaction(fn () => $report->delete());
        Storage::disk(self::DISK)->deleteDirectory("reports/{$report->id}");
    }

    private function makeThumbnails(Report $report): void
    {
        try {
            $disk = Storage::disk(self::DISK);

            foreach ($report->photos as $photo) {
                $thumb = "reports/{$report->id}/thumb_".pathinfo($photo->path, PATHINFO_FILENAME).'.jpg';

                if (Thumbnailer::make($disk->path($photo->path), $disk->path($thumb))) {
                    $photo->update(['thumb_path' => $thumb]);
                }
            }
        } catch (Throwable $e) {
            report($e); // thumbnail hanya optimasi; foto asli tetap dipakai
        }
    }

    private function notifyAdmin(Report $report): void
    {
        try {
            User::where('role', UserRole::Admin->value)->first()?->notify(new ReportSubmitted($report));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
