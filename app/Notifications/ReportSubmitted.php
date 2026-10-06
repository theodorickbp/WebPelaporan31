<?php

namespace App\Notifications;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Dikirim ke Admin/Toolman saat ada laporan baru. */
class ReportSubmitted extends Notification
{
    public function __construct(public Report $report) {}

    public function via(object $notifiable): array
    {
        return config('sipasar.mail_notifications') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Laporan baru {$this->report->code}")
            ->line("{$this->report->user->name} melaporkan: {$this->report->title}")
            ->line("Lokasi: {$this->report->location}")
            ->action('Tinjau laporan', route('admin.reports.show', $this->report));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_code' => $this->report->code,
            'title' => $this->report->title,
            'status' => ReportStatus::Submitted->value,
            'message' => "Laporan baru dari {$this->report->user->name} ({$this->report->location}).",
        ];
    }
}
