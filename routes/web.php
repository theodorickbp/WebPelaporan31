<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student;
use App\Http\Middleware\EnsureRole;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'landing'])->name('home');

/*
| Tamu. Hanya siswa yang bisa mendaftar; akun Admin/Toolman dibuat lewat seeder.
*/
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.store');

    // Lupa kata sandi (khusus siswa) lewat kode OTP ke email.
    Route::get('/lupa-sandi', [PasswordResetController::class, 'showRequest'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'sendOtp'])->middleware('throttle:5,15')->name('password.email');
    Route::get('/lupa-sandi/verifikasi', [PasswordResetController::class, 'showVerify'])->name('password.otp');
    Route::post('/lupa-sandi/verifikasi', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:8,15')->name('password.update');
});

/*
| Semua yang sudah login (siswa & admin). AuthenticateSession membuat
| "keluarkan perangkat lain" dan ganti kata sandi berlaku di semua perangkat.
*/
Route::middleware(['auth', AuthenticateSession::class])->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    Route::get('/foto/{photo}', [PhotoController::class, 'show'])->name('photos.show');
    Route::get('/foto/{photo}/kecil', [PhotoController::class, 'thumb'])->name('photos.thumb');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/kata-sandi', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/profil/keluar-perangkat-lain', [ProfileController::class, 'logoutOthers'])->name('profile.logout-others');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifikasi/{id}', [NotificationController::class, 'open'])->name('notifications.open');
});

/*
| Area siswa.
*/
Route::middleware(['auth', AuthenticateSession::class, EnsureRole::class.':student'])
    ->prefix('siswa')->name('student.')->group(function () {
        Route::get('/', [Student\ReportController::class, 'home'])->name('home');
        Route::get('/laporan', [Student\ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/baru', [Student\ReportController::class, 'create'])->name('reports.create');
        Route::post('/laporan', [Student\ReportController::class, 'store'])
            ->middleware('throttle:8,1')->name('reports.store');
        Route::get('/laporan/{report}', [Student\ReportController::class, 'show'])->name('reports.show');
        Route::delete('/laporan/{report}', [Student\ReportController::class, 'destroy'])->name('reports.destroy');
    });

/*
| Area Admin/Toolman.
*/
Route::middleware(['auth', AuthenticateSession::class, EnsureRole::class.':admin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\ReportController::class, 'home'])->name('home');
        Route::get('/laporan', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/{report}', [Admin\ReportController::class, 'show'])->name('reports.show');
        Route::post('/laporan/{report}/status', [Admin\ReportController::class, 'transition'])->name('reports.transition');
    });
