<?php

namespace App\Enums;

/**
 * Alur status laporan:
 *
 *   waiting ──> submitted ─┬─> pending ──> in_repair ──> review ──> done
 *   (antrean penuh)        └─> declined       ^            │
 *                                              └────────────┘ (dikembalikan Admin ke Toolman)
 *
 *  - waiting   : antrean Admin sedang penuh (maks. 50); otomatis naik jadi submitted (FIFO) saat ada slot kosong
 *  - submitted : ada di antrean Admin, menunggu ditinjau
 *  - pending   : disetujui Admin, belum ditugaskan ke Toolman
 *  - in_repair : ditugaskan ke Toolman dan sedang dikerjakan
 *  - review    : Toolman sudah mengirim hasil, menunggu diperiksa Admin
 *  - done      : Admin menyelesaikan laporan dan memberi informasi ke siswa
 */
enum ReportStatus: string
{
    case Waiting = 'waiting';
    case Submitted = 'submitted';
    case Declined = 'declined';
    case Pending = 'pending';
    case InRepair = 'in_repair';
    case Review = 'review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu antrean',
            self::Submitted => 'Menunggu ditinjau',
            self::Declined => 'Ditolak',
            self::Pending => 'Disetujui',
            self::InRepair => 'Dikerjakan',
            self::Review => 'Diperiksa Admin',
            self::Done => 'Selesai',
        };
    }

    /** Kunci warna untuk CSS (.chip-*, .tone-*). */
    public function tone(): string
    {
        return match ($this) {
            self::Waiting, self::Submitted => 'wait',
            self::Declined => 'bad',
            self::Pending => 'pending',
            self::InRepair => 'repair',
            self::Review => 'review',
            self::Done => 'done',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Waiting => 'clock',
            self::Submitted => 'inbox',
            self::Declined => 'x-circle',
            self::Pending => 'clock',
            self::InRepair => 'wrench',
            self::Review => 'search',
            self::Done => 'check',
        };
    }

    /** Judul entri di riwayat (timeline). */
    public function timelineLabel(): string
    {
        return match ($this) {
            self::Waiting => 'Laporan dikirim, menunggu antrean Admin',
            self::Submitted => 'Masuk antrean Admin',
            self::Declined => 'Laporan ditolak',
            self::Pending => 'Disetujui Admin',
            self::InRepair => 'Ditugaskan dan mulai dikerjakan',
            self::Review => 'Hasil pengerjaan dikirim ke Admin',
            self::Done => 'Selesai',
        };
    }

    /** Teks tombol admin untuk pindah KE status ini. */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Waiting => 'Antre',
            self::Submitted => 'Kirim',
            self::Declined => 'Tolak laporan',
            self::Pending => 'Setujui laporan',
            self::InRepair => 'Kembalikan ke Toolman',
            self::Review => 'Kirim hasil pengerjaan',
            self::Done => 'Selesaikan laporan',
        };
    }

    public function adminToast(): string
    {
        return match ($this) {
            self::Waiting, self::Submitted => 'Laporan dikirim.',
            self::Declined => 'Laporan ditolak dan siswa diberi tahu.',
            self::Pending => 'Laporan disetujui. Pilih Toolman untuk mengerjakannya.',
            self::InRepair => 'Laporan dikembalikan ke Toolman untuk dikerjakan ulang.',
            self::Review => 'Hasil pengerjaan dikirim ke Admin.',
            self::Done => 'Laporan diselesaikan dan siswa diberi tahu.',
        };
    }

    /** Pesan notifikasi untuk siswa pembuat laporan. */
    public function studentMessage(?string $toolman = null): string
    {
        return match ($this) {
            self::Waiting => 'Laporanmu terkirim, tetapi antrean Admin sedang penuh. Laporanmu akan diproses sesuai urutan.',
            self::Submitted => 'Laporanmu masuk antrean Admin dan akan segera ditinjau.',
            self::Declined => 'Laporanmu belum bisa diproses. Cek alasannya di detail laporan.',
            self::Pending => 'Laporanmu disetujui Admin dan sedang menunggu penugasan Toolman.',
            self::InRepair => $toolman
                ? "Laporanmu sedang dikerjakan oleh {$toolman}."
                : 'Laporanmu sedang dikerjakan oleh Toolman.',
            self::Review => 'Hasil pengerjaan sedang diperiksa Admin.',
            self::Done => 'Masalah sudah selesai diperbaiki. Terima kasih sudah melapor!',
        };
    }

    /** Perubahan status yang dilihat siswa (notifikasi). Status "review" hanya urusan internal. */
    public function notifiesStudent(): bool
    {
        return $this !== self::Review;
    }

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Waiting => [self::Submitted],
            self::Submitted => [self::Pending, self::Declined],
            self::Pending => [self::InRepair],
            self::InRepair => [self::Review],
            self::Review => [self::Done, self::InRepair],
            self::Declined, self::Done => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->transitions(), true);
    }

    /** Status yang menghitung ke "laporan sedang diproses" (batas 15). */
    public static function activeValues(): array
    {
        return [self::Pending->value, self::InRepair->value, self::Review->value];
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return [
            self::Waiting->value, self::Submitted->value, self::Pending->value,
            self::InRepair->value, self::Review->value,
        ];
    }
}
