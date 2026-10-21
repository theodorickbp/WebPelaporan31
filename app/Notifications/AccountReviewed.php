<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke pemilik akun saat pengajuannya disetujui atau ditolak.
 * Email selalu dicoba (seperti OTP), karena pemilik akun belum bisa masuk untuk melihat notifikasi.
 */
class AccountReviewed extends Notification
{
    public function __construct(
        public bool $approved,
        public ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->approved) {
            return (new MailMessage)
                ->subject('Akun SI-PASAR kamu sudah disetujui')
                ->greeting("Halo, {$notifiable->name}")
                ->line('Pengajuan akunmu sudah disetujui Admin. Sekarang kamu bisa masuk dan membuat laporan.')
                ->action('Masuk', route('login'));
        }

        return (new MailMessage)
            ->subject('Pengajuan akun SI-PASAR ditolak')
            ->greeting("Halo, {$notifiable->name}")
            ->line('Maaf, pengajuan akunmu belum bisa disetujui.')
            ->when(filled($this->reason), fn (MailMessage $m) => $m->line("Alasan: {$this->reason}"))
            ->line('Hubungi Toolman di sekolah kalau ini keliru.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'account_review' => true,
            'title' => $this->approved ? 'Akun disetujui' : 'Pengajuan akun ditolak',
            'message' => $this->approved
                ? 'Akunmu sudah aktif. Selamat datang di SI-PASAR!'
                : 'Pengajuan akunmu ditolak.'.(filled($this->reason) ? " Alasan: {$this->reason}" : ''),
        ];
    }
}
