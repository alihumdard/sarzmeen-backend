<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to Sarzameen!')
            ->greeting("Hello {$notifiable->name},")
            ->line('Welcome to **Sarzameen.com** — Pakistan\'s trusted property marketplace.')
            ->line('You can now browse properties, connect with agents, and list your own properties.')
            ->action('Explore Properties', url('/properties'))
            ->line('Thank you for joining us!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'message' => 'Welcome to Sarzameen! Start exploring properties.',
        ];
    }
}
