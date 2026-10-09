<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Auto-reply sent to the person who submitted an inquiry. */
class InquiryConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Inquiry $inquiry,
    ) {}

    public function via(object $notifiable): array
    {
        // Anonymous notifiables (guest submitters) have no database to write to.
        return $notifiable instanceof AnonymousNotifiable
            ? ['mail']
            : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->inquiry->property;
        $project = $this->inquiry->project;
        $listing = $property?->title ?? $project?->name;

        $base = rtrim((string) config('app.frontend_url'), '/');
        $listingUrl = match (true) {
            $property !== null => "{$base}/properties/{$property->slug}",
            $project !== null => "{$base}/projects/{$project->slug}",
            default => null,
        };

        return (new MailMessage)
            ->subject('We received your inquiry - '.config('app.name'))
            ->markdown('mail.inquiry.confirmation', [
                'inquiry' => $this->inquiry,
                'listing' => $listing,
                'listingUrl' => $listingUrl,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inquiry_confirmation',
            'inquiry_id' => $this->inquiry->id,
        ];
    }
}
