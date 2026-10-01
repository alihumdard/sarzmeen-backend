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
        $property = $this->inquiry->property;

        return (new MailMessage)
            ->subject('New Inquiry Received')
            ->greeting("Hello {$notifiable->name},")
            ->line("You have received a new inquiry from **{$this->inquiry->name}**.")
            ->when($property, fn (MailMessage $m) => $m->line("Property: **{$property->title}**"))
            ->line("Message: {$this->inquiry->message}")
            ->action('View Inquiry', url('/admin/inquiries'))
            ->line('Please respond promptly to maintain a great customer experience.');
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
        ];
    }
}
