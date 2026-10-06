<?php

namespace App\Enums;

/**
 * Alur status laporan:
 *
 *   submitted ─┬─> pending ──> in_repair ──> done
 *              └─> declined
 */
enum ReportStatus: string
{
    case Submitted = 'submitted';
    case Declined = 'declined';
    case Pending = 'pending';
    case InRepair = 'in_repair';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Menunggu ditinjau',
            self::Declined => 'Ditolak',
            self::Pending => 'Pending',
            self::InRepair => 'Perbaikan',
            self::Done => 'Selesai',
        };
    }

    /** Kunci warna untuk CSS (.chip-*, .tone-*). */
    public function tone(): string
    {
        return match ($this) {
            self::Submitted => 'wait',
            self::Declined => 'bad',
            self::Pending => 'pending',
            self::InRepair => 'repair',
            self::Done => 'done',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Submitted => 'inbox',
            self::Declined => 'x-circle',
            self::Pending => 'clock',
            self::InRepair => 'wrench',
            self::Done => 'check',
        };
    }

    /** Judul entri di riwayat (timeline). */
    public function timelineLabel(): string
    {
        return match ($this) {
            self::Submitted => 'Laporan dikirim',
            self::Declined => 'Laporan ditolak',
            self::Pending => 'Diterima, masuk antrean',
            self::InRepair => 'Mulai diperbaiki',
            self::Done => 'Selesai diperbaiki',
        };
    }

    /** Teks tombol admin untuk pindah KE status ini. */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Submitted => 'Kirim',
            self::Declined => 'Tolak laporan',
            self::Pending => 'Terima laporan',
            self::InRepair => 'Mulai perbaikan',
            self::Done => 'Tandai selesai',
        };
    }

    public function adminToast(): string
    {
        return match ($this) {
            self::Submitted => 'Laporan dikirim.',
            self::Declined => 'Laporan ditolak dan siswa diberi tahu.',
            self::Pending => 'Laporan diterima dan masuk antrean.',
            self::InRepair => 'Status diubah ke perbaikan.',
            self::Done => 'Laporan ditandai selesai dan siswa diberi tahu.',
        };
    }

    /** Pesan notifikasi untuk siswa pembuat laporan. */
    public function studentMessage(): string
    {
        return match ($this) {
            self::Submitted => 'Laporanmu terkirim.',
            self::Declined => 'Laporanmu belum bisa diproses. Cek alasannya di detail laporan.',
            self::Pending => 'Laporanmu diterima Toolman dan masuk antrean perbaikan.',
            self::InRepair => 'Toolman mulai memperbaiki masalah ini.',
            self::Done => 'Masalah sudah selesai diperbaiki. Terima kasih sudah melapor!',
        };
    }

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Submitted => [self::Pending, self::Declined],
            self::Pending => [self::InRepair],
            self::InRepair => [self::Done],
            self::Declined, self::Done => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->transitions(), true);
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return [self::Submitted->value, self::Pending->value, self::InRepair->value];
    }
}
