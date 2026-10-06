<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** `role` sengaja tidak fillable: role tidak boleh diisi lewat request. */
    protected $fillable = ['name', 'email', 'kelas', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['role' => 'student'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
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

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
