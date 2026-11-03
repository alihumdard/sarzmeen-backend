<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AdminReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportAnsweredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly AdminReport $report,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report_answered',
            'report_id' => $this->report->id,
            'status' => $this->report->status,
            'subject' => $this->report->subject,
            'preview' => $this->report->admin_reply
                ? str($this->report->admin_reply)->limit(80)->toString()
                : null,
        ];
    }
}
