<?php

return [

    // Batas unggahan foto bukti per laporan.
    'max_photos' => 3,
    'max_photo_kb' => 8192,

    // Kirim email juga (selain notifikasi di dalam aplikasi). Butuh konfigurasi MAIL_* di .env.
    'mail_notifications' => (bool) env('SIPASAR_MAIL_NOTIFICATIONS', false),

    // Ambang "menunggu lama" untuk pengingat visual ke Toolman (laporan status "submitted" yang belum ditinjau).
    'overdue_hours' => (int) env('SIPASAR_OVERDUE_HOURS', 48),

    // Foto bukti laporan yang sudah "Selesai"/"Ditolak" dihapus otomatis setelah sekian hari,
    // supaya penyimpanan tidak terus membesar. Data/riwayat laporannya SENDIRI (judul, status,
    // tanggal, catatan Toolman) tetap disimpan selamanya - yang dihapus cuma file foto aslinya.
    'photo_retention_days' => (int) env('SIPASAR_PHOTO_RETENTION_DAYS', 7),

    // Lupa kata sandi (khusus siswa) lewat kode OTP dikirim ke email.
    'otp' => [
        'length' => 6,
        'expires_minutes' => 10,
        'max_attempts' => 5,
    ],

    // Batas sistem.
    'max_approved_at_once' => 15,     // laporan yang boleh sedang diproses (disetujui s/d belum selesai) sekaligus
    'max_queue' => 50,                // laporan yang boleh masuk antrean Admin; sisanya menunggu (status "waiting")
    'max_reports_per_day' => 5,       // laporan per akun siswa per hari

    // 3 akun Toolman (dibuat oleh migrasi/ToolmanSeeder).
    'toolmen' => [
        ['name' => 'Toolman A', 'email' => 'toolmana@gmail.com', 'password' => 'toolman123'],
        ['name' => 'Toolman B', 'email' => 'toolmanb@gmail.com', 'password' => 'toolman231'],
        ['name' => 'Toolman C', 'email' => 'toolmanc@gmail.com', 'password' => 'toolman312'],
    ],

    // Akun Admin tunggal, dibuat oleh AdminSeeder (tidak ada halaman daftar untuk admin).
    'admin' => [
        'name' => env('ADMIN_NAME', 'Toolman'),
        'email' => env('ADMIN_EMAIL', 'toolman@sipasar.test'),
        'password' => env('ADMIN_PASSWORD', 'ganti-password-ini'),
    ],

];
