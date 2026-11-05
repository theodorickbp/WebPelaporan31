<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportAssigned;
use App\Notifications\ReportResultSubmitted;
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
                $this->consumeDailyQuota($user);

                // Antrean Admin dibatasi. Kalau penuh, laporan menunggu (status "waiting")
                // dan naik otomatis sesuai urutan kirim begitu ada slot kosong.
                $queued = Report::where('status', ReportStatus::Submitted->value)->count();
                $initial = $queued >= (int) config('sipasar.max_queue') ? ReportStatus::Waiting : ReportStatus::Submitted;

                $report = Report::create([
                    'code' => Report::generateCode(),
                    'user_id' => $user->id,
                    'category' => $data['category'],
                    'title' => $data['title'],
                    'location' => $data['location'],
                    'description' => $data['description'] ?? null,
                    'status' => $initial,
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
                    'to_status' => $initial,
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

        if ($report->status === ReportStatus::Submitted) {
            $this->notifyAdmin($report);
        }

        return $report;
    }

    /** Hari ini menurut waktu sekolah (WIB), dipakai untuk batas laporan harian. */
    private function today(): string
    {
        return now('Asia/Jakarta')->toDateString();
    }

    /** Sisa jatah laporan hari ini untuk siswa. */
    public function dailyRemaining(User $user): int
    {
        $fresh = User::query()->whereKey($user->getKey())->first(['id', 'daily_report_date', 'daily_report_count']);
        $used = $fresh && $fresh->getRawOriginal('daily_report_date') === $this->today()
            ? (int) $fresh->daily_report_count
            : 0;

        return max(0, (int) config('sipasar.max_reports_per_day') - $used);
    }

    /**
     * Pakai 1 jatah laporan harian (harus dipanggil di dalam transaksi).
     * Penghitung disimpan di tabel users, jadi membatalkan laporan tidak mengembalikan jatah.
     */
    private function consumeDailyQuota(User $user): void
    {
        $max = (int) config('sipasar.max_reports_per_day');
        $row = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
        $today = $this->today();
        $used = $row->getRawOriginal('daily_report_date') === $today ? (int) $row->daily_report_count : 0;

        if ($used >= $max) {
            throw ValidationException::withMessages([
                'limit' => "Batas {$max} laporan per hari sudah tercapai. Coba lagi besok.",
            ]);
        }

        $row->forceFill(['daily_report_date' => $today, 'daily_report_count' => $used + 1])->save();
    }

    /**
     * Memindahkan laporan ke status berikutnya (hanya jalur yang diizinkan enum).
     *
     * @param  User|null  $toolman  wajib saat Disetujui -> Dikerjakan (penugasan manual oleh Admin)
     */
    public function transition(Report $report, ReportStatus $to, User $actor, ?string $note = null, ?User $toolman = null): Report
    {
        $note = filled($note) ? trim($note) : null;
        $from = $report->status;
        $returned = false;

        DB::transaction(function () use (&$report, &$from, &$returned, $to, $actor, $note, $toolman) {
            $fresh = Report::whereKey($report->getKey())->lockForUpdate()->firstOrFail();

            if (! $fresh->status->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => 'Status laporan sudah berubah atau perubahan ini tidak diizinkan. Muat ulang halaman.',
                ]);
            }

            $from = $fresh->status;

            // Batas: maksimal N laporan disetujui/diproses sekaligus.
            if ($to === ReportStatus::Pending) {
                $max = (int) config('sipasar.max_approved_at_once');
                $active = Report::whereIn('status', ReportStatus::activeValues())->count();

                if ($active >= $max) {
                    throw ValidationException::withMessages([
                        'status' => "Batas {$max} laporan yang disetujui sekaligus sudah tercapai. Selesaikan dulu laporan yang sedang berjalan.",
                    ]);
                }
            }

            if ($to === ReportStatus::InRepair && $from === ReportStatus::Pending) {
                if (! $toolman || ! $toolman->isToolman()) {
                    throw ValidationException::withMessages(['toolman_id' => 'Pilih Toolman yang akan mengerjakan laporan ini.']);
                }
                $fresh->toolman_id = $toolman->id;
            }
            $returned = $to === ReportStatus::InRepair && $from === ReportStatus::Review;

            $fresh->status = $to;

            if ($to === ReportStatus::Review) {
                $fresh->work_note = $note; // laporan hasil dari Toolman
            }
            if ($note !== null && in_array($to, [ReportStatus::Declined, ReportStatus::Done], true)) {
                $fresh->admin_note = $note; // informasi untuk siswa
            }
            if ($to === ReportStatus::Done || $to === ReportStatus::Declined) {
                // "resolved_at" di sini berarti "mencapai status akhir" (Selesai ATAU Ditolak),
                // dipakai sebagai patokan kapan mulai dihitung retensi foto (lihat purgePhotosPastRetention).
                $fresh->resolved_at = now();
            }
            $fresh->save();

            $fresh->logs()->create([
                'user_id' => $actor->id,
                'toolman_id' => in_array($to, [ReportStatus::InRepair, ReportStatus::Review, ReportStatus::Done], true)
                    ? $fresh->toolman_id
                    : null,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
            ]);

            $report = $fresh;
        });

        Cache::forget('sipasar:report-status-counts');
        $report->load(['user', 'toolman']);

        // Keluar dari antrean Admin -> slot kosong untuk laporan yang menunggu.
        if ($from === ReportStatus::Submitted) {
            $this->promoteWaiting();
        }

        try {
            if ($to->notifiesStudent()) {
                $report->user->notify(new ReportStatusChanged($report, $to, $note));
            }
            if ($to === ReportStatus::InRepair && $report->toolman) {
                $report->toolman->notify(new ReportAssigned($report, $returned));
            }
            if ($to === ReportStatus::Review) {
                User::where('role', UserRole::Admin->value)->first()?->notify(new ReportResultSubmitted($report));
            }
        } catch (Throwable $e) {
            report($e); // gagal kirim notifikasi tidak boleh membatalkan perubahan status
        }

        return $report;
    }

    /** Admin menugaskan laporan (Disetujui) ke Toolman pilihannya. */
    public function assign(Report $report, User $toolman, User $admin): Report
    {
        return $this->transition($report, ReportStatus::InRepair, $admin, null, $toolman);
    }

    /** Toolman mengirim hasil pengerjaan ke Admin. */
    public function submitResult(Report $report, User $toolman, string $note): Report
    {
        return $this->transition($report, ReportStatus::Review, $toolman, $note);
    }

    /**
     * Naikkan laporan yang menunggu ke antrean Admin (urutan kirim, FIFO) selama masih ada slot.
     *
     * @return int jumlah laporan yang dinaikkan
     */
    public function promoteWaiting(): int
    {
        $promoted = collect();

        DB::transaction(function () use (&$promoted) {
            $free = (int) config('sipasar.max_queue')
                - Report::where('status', ReportStatus::Submitted->value)->count();

            if ($free <= 0) {
                return;
            }

            $promoted = Report::where('status', ReportStatus::Waiting->value)
                ->orderBy('created_at')->orderBy('id')->limit($free)->lockForUpdate()->get();

            foreach ($promoted as $report) {
                $report->status = ReportStatus::Submitted;
                $report->save();
                $report->logs()->create([
                    'user_id' => null,
                    'from_status' => ReportStatus::Waiting,
                    'to_status' => ReportStatus::Submitted,
                    'note' => null,
                ]);
            }
        });

        if ($promoted->isEmpty()) {
            return 0;
        }

        Cache::forget('sipasar:report-status-counts');

        foreach ($promoted as $report) {
            try {
                $report->load('user');
                $report->user->notify(new ReportStatusChanged($report, ReportStatus::Submitted));
                $this->notifyAdmin($report);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $promoted->count();
    }

    /** Siswa membatalkan laporannya sendiri (hanya saat masih "menunggu ditinjau" / "menunggu antrean"). */
    public function cancel(Report $report): void
    {
        $wasQueued = $report->status === ReportStatus::Submitted;

        DB::transaction(fn () => $report->delete());
        Storage::disk(self::DISK)->deleteDirectory("reports/{$report->id}");
        Cache::forget('sipasar:report-status-counts');

        if ($wasQueued) {
            $this->promoteWaiting();
        }
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
