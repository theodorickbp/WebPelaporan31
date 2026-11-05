<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class ToolmanSeeder extends Seeder
{
    /** Membuat 3 akun Toolman (idempoten; password akun yang sudah ada tidak ditimpa). */
    public function run(): void
    {
        foreach (config('sipasar.toolmen') as $t) {
            $user = User::where('email', $t['email'])->first() ?? new User;

            $user->forceFill([
                'name' => $t['name'],
                'email' => $t['email'],
                'role' => UserRole::Toolman,
                'account_status' => 'approved',
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            if (! $user->exists) {
                $user->password = $t['password'];
            }

            $user->save();
        }
    }
}
