<?php

namespace Database\Factories;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Report> */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Report::generateCode(),
            'user_id' => User::factory(),
            'category' => fake()->randomElement(ReportCategory::cases()),
            'title' => fake()->randomElement([
                'Proyektor tidak menyala', 'Mouse hilang dari meja 5', 'Kabel LAN putus',
                'Keyboard beberapa tombol macet', 'Stopkontak longgar', 'Internet lab sangat lambat',
            ]),
            'location' => fake()->randomElement(['Lab RPL 1', 'Lab RPL 2', 'Kelas XI TKJ 1', 'Ruang multimedia']),
            'description' => fake()->sentence(12),
            'status' => ReportStatus::Submitted,
        ];
    }

    public function status(ReportStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
