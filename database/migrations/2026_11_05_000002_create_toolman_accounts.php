<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Membuat 3 akun Toolman bila belum ada (tidak menimpa password akun yang sudah ada). */
    public function up(): void
    {
        foreach (config('sipasar.toolmen') as $t) {
            if (User::where('email', $t['email'])->exists()) {
                continue;
            }

            $user = new User(['name' => $t['name'], 'email' => $t['email'], 'password' => $t['password']]);
            $user->forceFill([
                'role' => UserRole::Toolman,
                'email_verified_at' => now(),
                'account_status' => 'approved',
            ])->save();
        }
    }

    public function down(): void
    {
        User::whereIn('email', array_column(config('sipasar.toolmen'), 'email'))
            ->where('role', UserRole::Toolman->value)->delete();
    }
};
