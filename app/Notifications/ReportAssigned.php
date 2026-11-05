<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Dikirim ke Toolman saat Admin menugaskan laporan (atau mengembalikannya untuk dikerjakan ulang). */
class ReportAssigned extends Notification
{
    public function __construct(public Report $report, public bool $returned = false) {}

    public function via(object $notifiable): array
    {
        return config('sipasar.mail_notifications') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tugas baru {$this->report->code}")
            ->line($this->message())
            ->action('Buka tugas', route('toolman.reports.show', $this->report));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_code' => $this->report->code,
            'title' => $this->report->title,
            'status' => $this->report->status->value,
            'message' => $this->message(),
        ];
    }

    private function message(): string
    {
        return $this->returned
            ? "Admin mengembalikan laporan \"{$this->report->title}\" untuk dikerjakan ulang."
            : "Kamu ditugaskan menangani laporan \"{$this->report->title}\" ({$this->report->location}).";
    }
}
