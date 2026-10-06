<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /** Admin melihat semua laporan; siswa hanya laporannya sendiri. */
    public function view(User $user, Report $report): bool
    {
        return $user->isAdmin() || $report->user_id === $user->id;
    }

    /** Siswa hanya boleh membatalkan laporannya selama belum ditinjau Toolman. */
    public function delete(User $user, Report $report): bool
    {
        return $report->user_id === $user->id && $report->status === ReportStatus::Submitted;
    }
}
