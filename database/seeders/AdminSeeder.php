<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * Membuat (atau memperbarui nama/email) satu-satunya akun Admin/Toolman.
     * Password hanya diisi saat akun pertama kali dibuat, jadi seeding ulang
     * tidak menimpa password yang sudah diganti Toolman.
     */
    public function run(): void
    {
        $cfg = config('sipasar.admin');

        if (app()->isProduction() && $cfg['password'] === 'ganti-password-ini') {
            throw new RuntimeException('Isi ADMIN_PASSWORD di .env sebelum seeding di production.');
        }

        $admin = User::where('role', UserRole::Admin->value)->first() ?? new User;

        $admin->forceFill([
            'name' => $cfg['name'],
            'email' => $cfg['email'],
            'role' => UserRole::Admin,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ]);

        if (! $admin->exists) {
            $admin->password = $cfg['password'];
        }

        $admin->save();
    }
}
