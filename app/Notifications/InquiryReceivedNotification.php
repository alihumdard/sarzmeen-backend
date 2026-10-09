<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InquiryReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Inquiry $inquiry,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $listing = $this->inquiry->property?->title
            ?? $this->inquiry->project?->name;

        $subject = $listing
            ? "New Inquiry: {$listing}"
            : "New Inquiry from {$this->inquiry->name}";

        return (new MailMessage)
            ->subject($subject)
            // Lets the agent hit reply and reach the lead directly.
            ->replyTo($this->inquiry->email, $this->inquiry->name)
            ->markdown('mail.inquiry.received', [
                'inquiry' => $this->inquiry,
                'notifiable' => $notifiable,
                'listing' => $listing,
                'actionUrl' => rtrim((string) config('app.frontend_url'), '/').'/admin/inquiries',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inquiry_received',
            'inquiry_id' => $this->inquiry->id,
            'name' => $this->inquiry->name,
            'email' => $this->inquiry->email,
            'message' => str($this->inquiry->message)->limit(100)->toString(),
            'property' => $this->inquiry->property?->title,
            'project' => $this->inquiry->project?->name,
        ];
    }
}
