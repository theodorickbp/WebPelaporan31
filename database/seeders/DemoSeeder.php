<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Data contoh untuk demo: php artisan db:seed --class=DemoSeeder (password siswa: "password"). */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::factory()->count(4)->create();

        foreach (ReportStatus::cases() as $status) {
            foreach ($students->random(2) as $student) {
                $report = Report::factory()->status($status)->create(['user_id' => $student->id]);
                $report->logs()->create([
                    'user_id' => $student->id,
                    'to_status' => ReportStatus::Submitted,
                    'note' => 'Laporan dikirim',
                ]);
            }
        }
    }
}
