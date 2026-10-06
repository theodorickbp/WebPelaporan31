<?php

namespace App\Notifications;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Dikirim ke siswa setiap kali status laporannya berubah. */
class ReportStatusChanged extends Notification
{
    public function __construct(
        public Report $report,
        public ReportStatus $status,
        public ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return config('sipasar.mail_notifications') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Laporan {$this->report->code}: {$this->status->label()}")
            ->greeting("Halo, {$notifiable->name}")
            ->line($this->status->studentMessage())
            ->line("Laporan: {$this->report->title}")
            ->when(filled($this->note), fn (MailMessage $m) => $m->line("Catatan Toolman: {$this->note}"))
            ->action('Lihat laporan', route('student.reports.show', $this->report));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_code' => $this->report->code,
            'title' => $this->report->title,
            'status' => $this->status->value,
            'message' => $this->status->studentMessage(),
        ];
    }
}
