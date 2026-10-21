<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Dikirim ke Admin/Toolman saat ada pengajuan akun baru. */
class AccountRequested extends Notification
{
    public function __construct(public User $applicant) {}

    public function via(object $notifiable): array
    {
        return config('sipasar.mail_notifications') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengajuan akun baru: '.$this->applicant->name)
            ->line("{$this->applicant->name} ({$this->applicant->role->label()}) mengajukan akun SI-PASAR.")
            ->action('Tinjau pengajuan', route('admin.accounts.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'account_request' => true,
            'title' => 'Pengajuan akun baru',
            'message' => "{$this->applicant->name} ({$this->applicant->role->label()}) mengajukan akun dan menunggu persetujuan.",
        ];
    }
}
