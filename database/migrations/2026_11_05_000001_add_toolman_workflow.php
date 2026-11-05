<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role Toolman + alur penugasan:
     * - reports.toolman_id  : Toolman yang ditugaskan Admin
     * - reports.work_note   : laporan hasil pengerjaan dari Toolman
     * - report_status_logs.toolman_id : Toolman yang menangani saat entri riwayat dibuat
     * - users.daily_report_* : penghitung batas 5 laporan per hari (tidak bisa dicurangi dengan membatalkan laporan)
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('toolman_id')->nullable()->index();
            $table->text('work_note')->nullable();
        });

        Schema::table('report_status_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('toolman_id')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->date('daily_report_date')->nullable();
            $table->unsignedSmallInteger('daily_report_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['daily_report_date', 'daily_report_count']);
        });

        Schema::table('report_status_logs', function (Blueprint $table) {
            $table->dropColumn('toolman_id');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['toolman_id']);
            $table->dropColumn(['toolman_id', 'work_note']);
        });
    }
};
