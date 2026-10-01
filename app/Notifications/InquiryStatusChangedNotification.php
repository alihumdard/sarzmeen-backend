<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InquiryStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Inquiry $inquiry,
        private readonly string $oldStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inquiry_status_changed',
            'inquiry_id' => $this->inquiry->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->inquiry->status->value,
            'property' => $this->inquiry->property?->title,
        ];
    }
}
