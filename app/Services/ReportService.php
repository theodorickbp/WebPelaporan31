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
            if ($to === ReportStatus::Done || $to === ReportStatus::Declined) {
                // "resolved_at" di sini berarti "mencapai status akhir" (Selesai ATAU Ditolak),
                // dipakai sebagai patokan kapan mulai dihitung retensi foto (lihat purgePhotosPastRetention).
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

    /**
     * Hapus FOTO (file + baris report_photos) dari laporan yang sudah "Selesai"/"Ditolak"
     * lebih dari sipasar.photo_retention_days hari. Baris laporannya sendiri TIDAK disentuh -
     * judul, status, tanggal, catatan Toolman, dan riwayatnya tetap ada selamanya.
     *
     * @return int jumlah laporan yang foto-nya dibersihkan
     */
    public function purgePhotosPastRetention(): int
    {
        $cutoff = now()->subDays((int) config('sipasar.photo_retention_days'));

        $reports = Report::query()
            ->whereIn('status', [ReportStatus::Done->value, ReportStatus::Declined->value])
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $cutoff)
            ->whereHas('photos') // lewati yang fotonya sudah pernah dibersihkan sebelumnya
            ->get(['id']);

        foreach ($reports as $report) {
            Storage::disk(self::DISK)->deleteDirectory("reports/{$report->id}");
            $report->photos()->delete();
        }

        return $reports->count();
    }

    /**
     * Pembersihan foto TANPA cron job: dipanggil dari halaman yang sering dibuka Toolman
     * (lihat Admin\ReportController::home), tapi proses hapusnya sendiri cuma benar-benar
     * jalan sekali per jam (di-debounce lewat cache) supaya tidak query/scan tiap buka halaman.
     */
    public function maybePurgePhotosPastRetention(): void
    {
        Cache::remember('sipasar:photo-purge:last-run', 3600, function () {
            $this->purgePhotosPastRetention();

            return now();
        });
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
