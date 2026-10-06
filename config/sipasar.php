<?php

return [

    // Batas unggahan foto bukti per laporan.
    'max_photos' => 3,
    'max_photo_kb' => 8192,

    // Kirim email juga (selain notifikasi di dalam aplikasi). Butuh konfigurasi MAIL_* di .env.
    'mail_notifications' => (bool) env('SIPASAR_MAIL_NOTIFICATIONS', false),

    // Ambang "menunggu lama" untuk pengingat visual ke Toolman (laporan status "submitted" yang belum ditinjau).
    'overdue_hours' => (int) env('SIPASAR_OVERDUE_HOURS', 48),

    // Lupa kata sandi (khusus siswa) lewat kode OTP dikirim ke email.
    'otp' => [
        'length' => 6,
        'expires_minutes' => 10,
        'max_attempts' => 5,
    ],

    // Akun Admin/Toolman tunggal, dibuat oleh AdminSeeder (tidak ada halaman daftar untuk admin).
    'admin' => [
        'name' => env('ADMIN_NAME', 'Toolman'),
        'email' => env('ADMIN_EMAIL', 'toolman@sipasar.test'),
        'password' => env('ADMIN_PASSWORD', 'ganti-password-ini'),
    ],

];
