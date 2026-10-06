<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Kode OTP ganti kata sandi. SELALU lewat email, tidak dipengaruhi
 * pengaturan sipasar.mail_notifications - ini email keamanan, bukan notifikasi biasa.
 */
class OtpPasswordReset extends Notification
{
    public function __construct(public string $otp, public int $expiresInMinutes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $big = "<div style=\"font-size:28px;font-weight:700;letter-spacing:8px;text-align:center;margin:16px 0\">{$this->otp}</div>";

        return (new MailMessage)
            ->subject('Kode OTP ganti kata sandi SI-PASAR')
            ->greeting("Halo, {$notifiable->name}")
            ->line('Gunakan kode berikut untuk mengganti kata sandi akunmu:')
            ->line(new HtmlString($big))
            ->line("Kode ini berlaku {$this->expiresInMinutes} menit dan hanya bisa dipakai sekali.")
            ->line('Kalau kamu tidak meminta ganti kata sandi, abaikan email ini - akunmu tetap aman.');
    }
}
