<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /** Admin melihat semua laporan; Toolman hanya yang ditugaskan padanya; siswa hanya laporannya sendiri. */
    public function view(User $user, Report $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isToolman()) {
            return $report->toolman_id === $user->id;
        }

        return $report->user_id === $user->id;
    }

    /** Siswa hanya boleh membatalkan laporannya selama belum ditinjau Admin (antrean/menunggu antrean). */
    public function delete(User $user, Report $report): bool
    {
        return $report->user_id === $user->id
            && in_array($report->status, [ReportStatus::Submitted, ReportStatus::Waiting], true);
    }

    /** Toolman mengirim hasil pengerjaan: hanya untuk laporannya sendiri yang sedang dikerjakan. */
    public function work(User $user, Report $report): bool
    {
        return $user->isToolman()
            && $report->toolman_id === $user->id
            && $report->status === ReportStatus::InRepair;
    }
}
