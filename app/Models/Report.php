<?php

namespace App\Models;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'user_id', 'category', 'title', 'location',
        'description', 'status', 'admin_note', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => ReportCategory::class,
            'status' => ReportStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /** URL memakai kode tiket (SP-XXXXXX), bukan id berurutan. */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'SP-';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReportPhoto::class)->orderBy('sort')->orderBy('id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReportStatusLog::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Setiap laporan WAJIB punya minimal 1 foto saat dibuat (lihat StoreReportRequest), jadi
     * kalau laporan yang sudah "Selesai"/"Ditolak" ternyata fotonya kosong sekarang, itu pasti
     * karena sudah dihapus otomatis oleh ReportService::purgePhotosPastRetention(), bukan
     * karena memang tidak pernah ada. Dipakai untuk menampilkan catatan di halaman detail.
     */
    public function wasPhotoPurged(): bool
    {
        return $this->photos->isEmpty()
            && in_array($this->status, [ReportStatus::Done, ReportStatus::Declined], true);
    }

    /** Laporan yang belum ditinjau (masih "submitted") dan sudah melewati ambang waktu di config. Dipakai untuk pengingat visual ke Toolman. */
    public function isOverdue(): bool
    {
        return $this->status === ReportStatus::Submitted
            && $this->created_at->lt(now()->subHours((int) config('sipasar.overdue_hours')));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('code', 'like', $like)
                ->orWhere('title', 'like', $like)
                ->orWhere('location', 'like', $like)
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like));
        });
    }
}
