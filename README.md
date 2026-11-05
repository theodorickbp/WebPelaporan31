# SI-PASAR (Sistem Laporan Pengaduan Sarana)

Website Laravel untuk melaporkan kerusakan sarana kelas (jaringan LAN, mouse/keyboard hilang atau rusak,
kabel listrik, proyektor, dll.) lengkap dengan **foto bukti**. Siswa melapor dari HP, Toolman memprosesnya,
dan siswa diberi tahu di setiap tahap.

## Aktor dan fitur

| Aktor | Bisa |
|---|---|
| **Siswa** | Mengajukan akun (harus disetujui Admin sebelum bisa masuk), edit profil, ganti kata sandi, membuat laporan dengan foto (kamera / galeri / seret & lepas), memantau status, membatalkan laporan yang belum ditinjau, menerima notifikasi |
| **Admin** (hanya 1 akun) | Menyetujui akun siswa, melihat semua laporan (cari + filter), **setujui/tolak** laporan (maks. 15 disetujui sekaligus), **memilih Toolman secara manual**, memeriksa hasil pengerjaan, lalu menyelesaikan laporan dan memberi informasi ke siswa |
| **Toolman** (3 akun) | Hanya melihat laporan yang ditugaskan padanya, mengerjakan, lalu mengirim hasil pengerjaan ke Admin |

- Admin **tidak bisa mendaftar**. Akun dibuat oleh `AdminSeeder` dari `.env`, dan model `User` menolak (`DomainException`) pembuatan admin kedua.
- Role tidak pernah dibaca dari request, jadi pendaftaran selalu menghasilkan siswa.

### Alur status

```
Menunggu ditinjau ─┬─> Pending ──> Perbaikan ──> Selesai
                   └─> Ditolak (wajib beri alasan)
```

Aturan alur ada di satu tempat (`App\Enums\ReportStatus::transitions()`), diberlakukan di `ReportService::transition()`
dengan `lockForUpdate`, dan setiap perubahan tercatat di `report_status_logs` (tampil sebagai riwayat).

### Foto bukti

- **HP**: tombol "Ambil foto" membuka aplikasi kamera (`capture`), "Pilih dari galeri" membuka galeri.
- **Laptop/PC**: "Ambil foto" membuka kamera browser (`getUserMedia`), atau **seret & lepas** / **Ctrl+V** ke kotak unggah.
- Foto JPG/JPEG, PNG, GIF, dan BMP didukung (WebP tidak diterima). Foto dikecilkan di browser (maks 1600 px, JPEG) sebelum dikirim, jadi hemat kuota dan aman dari batas upload PHP.
- Jika kamera browser tersedia dan izin diberikan, kamera langsung terbuka. Jika tidak tersedia/ditolak, pemilih foto dari folder perangkat dibuka sebagai alternatif.
- Maks 3 foto per laporan (`config/sipasar.php`).
- Foto dibuka di **pratinjau layar penuh dengan zoom**: cubit / scroll / klik dua kali, geser, tombol +/-/reset, panah antar foto.
- Foto disimpan di disk **privat** (`storage/app/private`) dan hanya dilayani lewat `PhotoController`, jadi hanya pemilik laporan dan Toolman yang bisa melihatnya. Tidak perlu `storage:link`.

### Notifikasi

Notifikasi di dalam aplikasi (ikon lonceng) untuk siswa saat status berubah, dan untuk Toolman saat ada laporan baru.
Email opsional: isi `MAIL_*` lalu set `SIPASAR_MAIL_NOTIFICATIONS=true`.

## Persetujuan akun

- Pendaftaran siswa menyimpan akun dengan status `pending`; pendaftar tidak otomatis login.
- Admin membuka menu **Akun**, lalu memilih Setujui atau Tolak. Pengajuan baru juga membuat notifikasi di dalam aplikasi untuk Admin.
- Akun lama tetap berstatus `approved` melalui nilai default migrasi, sehingga akun yang sudah ada tidak terkunci.

## Pemasangan

Butuh PHP 8.2+, Composer, dan koneksi internet.

```bash
./install.sh sipasar
cd sipasar && php artisan serve --host=0.0.0.0
```

Skrip membuat proyek Laravel baru, menyalin kode ini di atasnya, mengatur `.env` (bahasa `id`, zona waktu `Asia/Jakarta`,
sesi di database, akun Toolman), lalu menjalankan migrasi. Kata sandi Toolman acak dan dicetak di akhir kecuali kamu
mengisi `ADMIN_PASSWORD` sendiri.

Pemasangan manual: salin folder `app config database lang public resources routes tests` ke proyek Laravel 11/12,
tambahkan `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` ke `.env`, lalu `php artisan migrate --seed`.
Jangan menjalankan `make:notifications-table`: migrasi tabel `notifications` sudah disertakan.

Data contoh: `php artisan db:seed --class=DemoSeeder` (siswa demo berkata sandi `password`).

## Tes

```bash
php artisan test
```

Mencakup pendaftaran, aturan satu admin, pembatasan akses, alur laporan dan foto, transisi status, dan notifikasi.
Tes yang membuat gambar palsu membutuhkan ekstensi PHP GD.

## Catatan produksi

- **HTTPS wajib** agar kamera browser di laptop (`getUserMedia`) jalan. Kamera di HP tetap jalan lewat HTTP.
- `php.ini`: `upload_max_filesize=10M`, `post_max_size=32M`, ekstensi `gd`, `mbstring`, `pdo_*`.
- Di belakang reverse proxy, pertimbangkan `trustProxies` di `bootstrap/app.php`.
- Set `APP_ENV=production`, `APP_DEBUG=false`. Seeder menolak berjalan di production dengan kata sandi bawaan.
- Cadangkan database **dan** `storage/app/private` (isinya foto bukti).
- Tampilan tanpa build step: CSS dan JS ada di `public/`. Font memakai Google Fonts dengan cadangan font sistem.

## Foto bukti dihapus otomatis setelah laporan selesai

Supaya penyimpanan tidak terus membesar, foto bukti pada laporan yang sudah **"Selesai"** atau **"Ditolak"** dihapus otomatis **7 hari** setelah mencapai status itu (`SIPASAR_OVERDUE_HOURS` beda hal, ini `SIPASAR_PHOTO_RETENTION_DAYS` di `.env`).

- **Yang dihapus**: cuma file fotonya (asli + thumbnail) dan baris `report_photos`.
- **Yang TIDAK dihapus**: baris laporannya sendiri - judul, lokasi, status, tanggal, catatan Toolman, dan seluruh riwayat (`report_status_logs`) tetap ada **selamanya**. Jadi histori "bulan ini ada berapa laporan" tidak pernah hilang, yang hilang cuma gambar aslinya.
- Halaman detail laporan (siswa & Toolman) otomatis menampilkan catatan "Foto bukti sudah dihapus otomatis..." kalau ini terjadi, supaya tidak membingungkan.
- **Tanpa cron job** (sengaja, karena banyak hosting gratis tidak menyediakan cron): pengecekan dijalankan "sambil lalu" setiap kali Toolman membuka halaman Ringkasan (`Admin\ReportController::home()`), tapi proses hapusnya sendiri di-debounce lewat cache supaya maksimal jalan sekali per jam, bukan setiap request.
- Logikanya di `ReportService::purgePhotosPastRetention()` (bisa dites langsung) dan `::maybePurgePhotosPastRetention()` (versi yang di-debounce, dipanggil dari controller).

## Lupa kata sandi (khusus siswa)

Lewat kode OTP 6 digit ke email, bukan link:

1. Siswa masuk email di `/lupa-sandi` -> sistem membuat OTP, hash-nya disimpan (tabel `password_reset_otps`, 1 baris per user), dikirim lewat email (`OtpPasswordReset`, SELALU lewat mail terlepas dari `sipasar.mail_notifications`).
2. Siswa masukkan OTP + kata sandi baru di `/lupa-sandi/verifikasi`.
3. Maksimal 5 kali salah (`sipasar.otp.max_attempts`) sebelum OTP itu hangus dan harus minta baru. Kadaluwarsa 10 menit (`sipasar.otp.expires_minutes`).
4. Setelah berhasil, semua sesi lain (perangkat lain) ikut dikeluarkan.

**Admin/Toolman TIDAK bisa lewat sini** - kalau emailnya dimasukkan, sistem tetap merespons pesan netral yang sama seperti email biasa ("kalau email itu terdaftar sebagai siswa, kode sudah dikirim"), tanpa mengirim apa pun, supaya tidak ada yang bisa mengecek dari respons server apakah suatu email itu akun admin. Kalau Toolman sendiri lupa sandi, penggantinya manual lewat `AdminSeeder`/database oleh pengelola sekolah.

## Pengingat "menunggu lama" untuk Toolman

Laporan berstatus "Menunggu ditinjau" yang sudah melewati **48 jam** (`sipasar.overdue_hours` di `.env` lewat `SIPASAR_OVERDUE_HOURS`) diberi label merah "Menunggu lama, segera ditinjau" di daftar Toolman. Sengaja hanya status itu (laporan yang sudah pending/perbaikan tidak dianggap terlantar dengan cara ini).

## PWA (bisa dipasang di HP)

Bukan aplikasi store terpisah - ini murni fitur browser (`manifest.webmanifest` + `public/sw.js`). Service worker HANYA menyimpan cache untuk file statis (CSS/JS/ikon), TIDAK pernah menyimpan data laporan/notifikasi (itu wajib selalu segar dari server).

- **Android/desktop Chrome/Edge**: banner "Pasang" muncul otomatis (event `beforeinstallprompt`), atau lewat ikon instal di address bar.
- **iOS Safari**: tidak ada prompt otomatis (keterbatasan Apple) - banner menampilkan instruksi "Bagikan -> Tambah ke Layar Utama".
- Bisa ditutup, tidak dipaksakan tiap buka (tersimpan di `localStorage`).

## Optimasi performa

- **Minifikasi nyata** (bukan skrip regex buatan sendiri): `terser` untuk JS, `clean-css` untuk CSS lewat `npm run build` (lihat `scripts/build-assets.js`). Hasil `.min.*` sudah di-commit ke `public/` supaya server produksi tidak perlu Node.js sama sekali - `npm` hanya dipakai saat development untuk build ulang setelah mengedit source (`sipasar.css`, `app.js`, dst).
- **JS dimuat per-halaman** ("code splitting" versi tanpa bundler): `zoom-viewer.js` dan `photo-uploader.js` hanya dimuat di halaman yang benar-benar punya galeri/uploader foto (lewat komponen `<x-zoom-viewer-loader />` dan `@push('scripts')`), bukan di semua halaman aplikasi.
- **Cache query mahal**: rekap jumlah laporan per status (`statusCounts()`, dipakai di Ringkasan & Daftar laporan Toolman) di-cache 30 detik dan langsung di-invalidate begitu ada laporan baru/status berubah (`Cache::forget` di `ReportService`).
- **N+1 query**: sudah diaudit - semua listing (tiket, galeri, riwayat) sejak awal memakai eager loading (`with(['user','photos'])`, `load(['photos','logs.user'])`). Tidak ditemukan N+1 tersisa.
- **Connection pooling**: PHP-FPM tidak punya pooling koneksi DB bawaan seperti Node (beda model proses). Untuk skala satu sekolah, ini nyaris pasti tidak perlu. Kalau suatu saat memang mau dicoba, opsi paling praktis adalah *persistent connection* PDO - tambahkan manual ke `config/database.php` pada koneksi `mysql`: `'options' => [PDO::ATTR_PERSISTENT => (bool) env('DB_PERSISTENT', false)]`. Jangan nyalakan tanpa benar-benar mengukur ada bottleneck nyata dulu; persistent connection punya risiko state PDO "nempel" antar-request kalau tidak dites dengan hati-hati.
- **Loading skeleton**: thumbnail tiket, galeri, dan draft foto unggahan menampilkan shimmer sampai gambarnya benar-benar termuat (`.is-loading` + `data-loaded`, lihat `app.js`).
- **Kompresi foto**: sudah ada dari awal - dikecilkan ke JPEG di browser sebelum diunggah (lihat bagian Foto bukti).

### Audit Lighthouse (nyata, bukan estimasi)

Dijalankan dengan Lighthouse asli terhadap file CSS/JS produksi (`.min.*`) yang sama seperti yang dikirim ke browser, disimulasikan sebagai perangkat mobile dengan throttling jaringan:

| Kategori | Skor |
|---|---|
| Performance | 100/100 |
| Accessibility | 100/100 |
| Best Practices | 100/100 |
| SEO | 100/100 |

FCP ~1.0s, LCP ~1.1s, TBT 0ms, CLS 0.

**Batasan pengujian ini, disebutkan supaya tidak menyesatkan:** diuji di halaman landing yang disajikan dari server statis lokal, bukan lewat PHP-FPM + MySQL sungguhan (Composer/Packagist tidak bisa diakses di lingkungan pembuatan proyek ini, sehingga proyek Laravel penuh belum pernah benar-benar dijalankan). Yang terukur di sini murni pengiriman HTML/CSS/JS ke browser (parsing, render-blocking, aksesibilitas markup) - bukan waktu respons server/database sungguhan. Setelah `install.sh` dijalankan di komputer dengan PHP+Composer, jalankan `npx lighthouse http://alamat-aplikasimu --view` untuk audit menyeluruh termasuk waktu server.

## Belum ada

- Ekspor laporan (CSV/PDF) dan statistik bulanan - sengaja tidak dibuat (keputusan produk, bukan keterbatasan teknis).

## Peta kode

```
app/Enums/ReportStatus.php        alur & label status
app/Services/ReportService.php    buat laporan, pindah status, batalkan
app/Http/Controllers/Student|Admin
app/Policies/ReportPolicy.php     siapa boleh melihat/membatalkan
public/js/photo-uploader.js       kamera, galeri, drag&drop, kompres
public/js/zoom-viewer.js          pratinjau + zoom foto
resources/views/components        tiket laporan, jalur status, galeri, ikon
```
