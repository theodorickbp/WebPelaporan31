<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'kelas' => fake()->randomElement(['X RPL 1', 'X TKJ 2', 'XI RPL 1', 'XI TKJ 1', 'XII RPL 2']),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin, 'kelas' => null]);
    }


    public function pending(): static
    {
        return $this->state(fn () => ['account_status' => \App\Enums\AccountStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'account_status' => \App\Enums\AccountStatus::Rejected,
            'rejection_reason' => 'Data tidak cocok.',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
