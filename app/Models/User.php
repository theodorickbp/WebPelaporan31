<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * `role`, `account_status`, dan `nomor_induk` sengaja tidak fillable:
     * role/status tidak boleh diisi mentah dari request, dan nomor induk hanya diisi saat pengajuan.
     */
    protected $fillable = ['name', 'email', 'kelas', 'phone', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['role' => 'student', 'account_status' => 'approved'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'account_status' => AccountStatus::class,
        ];
    }

    protected static function booted(): void
    {
        // Aturan bisnis: hanya boleh ada SATU akun Admin/Toolman.
        static::saving(function (User $user) {
            if ($user->role !== UserRole::Admin) {
                return;
            }

            $otherAdminExists = static::query()
                ->where('role', UserRole::Admin->value)
                ->when($user->exists, fn ($q) => $q->whereKeyNot($user->getKey()))
                ->exists();

            if ($otherAdminExists) {
                throw new \DomainException('Hanya boleh ada satu akun Admin/Toolman.');
            }
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    /** Hanya akun yang sudah disetujui yang boleh masuk. */
    public function isApproved(): bool
    {
        return ($this->account_status ?? AccountStatus::Approved) === AccountStatus::Approved;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('account_status', AccountStatus::Pending->value);
    }

    /** Teks singkat di bawah nama pada sidebar. */
    public function roleSubtitle(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'Admin / Toolman',
            UserRole::Teacher => 'Guru',
            default => filled($this->kelas) ? 'Kelas '.$this->kelas : 'Siswa',
        };
    }

    /** Keterangan pelapor yang tampil di tiket laporan (sisi Toolman). */
    public function reporterLabel(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'Admin',
            UserRole::Teacher => 'Guru',
            default => filled($this->kelas) ? 'kelas '.$this->kelas : 'Siswa',
        };
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
