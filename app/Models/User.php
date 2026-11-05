<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\AccountStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'kelas', 'phone', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['role' => 'student'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'account_status' => AccountStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->role !== UserRole::Admin) {
                return;
            }

            $otherAdminExists = static::query()
                ->where('role', UserRole::Admin->value)
                ->when($user->exists, fn ($q) => $q->whereKeyNot($user->getKey()))
                ->exists();

            if ($otherAdminExists) {
                throw new \DomainException('Hanya boleh ada satu akun Admin.');
            }
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isToolman(): bool
    {
        return $this->role === UserRole::Toolman;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    /** Admin atau Toolman (bukan siswa). */
    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isToolman();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** Laporan yang ditugaskan ke Toolman ini. */
    public function assignedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'toolman_id');
    }

    public function pending()
    {
        return $this->reports()->whereIn('status', ['pending', 'submitted'])->count();
    }
}