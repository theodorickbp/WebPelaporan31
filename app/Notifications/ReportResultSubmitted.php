<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Dikirim ke Admin saat Toolman mengirim hasil pengerjaan. */
class ReportResultSubmitted extends Notification
{
    public function __construct(public Report $report) {}

    public function via(object $notifiable): array
    {
        return config('sipasar.mail_notifications') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Hasil pengerjaan {$this->report->code}")
            ->line($this->message())
            ->action('Periksa hasil', route('admin.reports.show', $this->report));
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
        $name = $this->report->toolman?->name ?? 'Toolman';

        return "{$name} mengirim hasil pengerjaan \"{$this->report->title}\". Silakan periksa.";
    }
}
