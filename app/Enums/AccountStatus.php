<?php

namespace App\Enums;

/** Status persetujuan akun. Akun baru berstatus Pending sampai Admin/Toolman memutuskan. */
enum AccountStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    /** Kelas CSS untuk chip (memakai chip yang sudah ada di sipasar.css). */
    public function chip(): string
    {
        return match ($this) {
            self::Pending => 'chip-pending',
            self::Approved => 'chip-done',
            self::Rejected => 'chip-bad',
        };
    }
}
