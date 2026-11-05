<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportStatusLog extends Model
{
    protected $fillable = ['user_id', 'toolman_id', 'from_status', 'to_status', 'note'];

    protected function casts(): array
    {
        return [
            'from_status' => ReportStatus::class,
            'to_status' => ReportStatus::class,
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Toolman yang menangani laporan saat entri riwayat ini dibuat. */
    public function toolman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'toolman_id');
    }
}
