<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data pengajuan akun + status persetujuan.
     * Default status 'approved' supaya akun yang SUDAH ada (Toolman dan siswa lama)
     * tetap bisa masuk; hanya pendaftar baru yang diset 'pending' oleh aplikasi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_induk', 20)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->text('registration_reason')->nullable();
            $table->string('account_status', 20)->default('approved')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nomor_induk']);
            $table->dropIndex(['account_status']);
            $table->dropColumn([
                'nomor_induk',
                'phone',
                'registration_reason',
                'account_status',
                'reviewed_at',
                'rejection_reason',
            ]);
        });
    }
};
