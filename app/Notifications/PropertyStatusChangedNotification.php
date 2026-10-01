<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PropertyStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Property $property,
        private readonly string $oldStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = ucfirst($this->property->status->value);

        return (new MailMessage)
            ->subject("Property {$status}: {$this->property->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your property **{$this->property->title}** has been **{$status}**.")
            ->action('View Property', url("/properties/{$this->property->slug}"))
            ->line('Thank you for listing with Sarzameen.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'property_status_changed',
            'property_id' => $this->property->id,
            'title' => $this->property->title,
            'slug' => $this->property->slug,
            'old_status' => $this->oldStatus,
            'new_status' => $this->property->status->value,
        ];
    }
}
